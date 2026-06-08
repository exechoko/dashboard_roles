<?php

namespace App\Console;

use App\Services\TelegramService;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Cache;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('tareas:generar')->dailyAt('01:00')
            ->onSuccess(function () {
                app(TelegramService::class)->notificarScheduleCompletado('tareas:generar');
            })
            ->onFailure(function () {
                app(TelegramService::class)->notificarScheduleFallido('tareas:generar', 'El comando finalizó con error.');
            });

        $schedule->command('tareas:avisar')->dailyAt('08:00')
            ->onSuccess(function () {
                app(TelegramService::class)->notificarScheduleCompletado('tareas:avisar');
            })
            ->onFailure(function () {
                app(TelegramService::class)->notificarScheduleFallido('tareas:avisar', 'El comando finalizó con error.');
            });

        $schedule->command('cecoco:importar-dia-anterior')->dailyAt('06:00')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/cecoco_importacion.log'))
            ->onSuccess(function () {
                // Invalida el caché de estadísticas Cecoco del dashboard para que
                // refleje los datos recién importados sin esperar al TTL.
                Cache::forget('dashboard.home.cecoco.' . Carbon::yesterday()->toDateString());
                app(TelegramService::class)->notificarScheduleCompletado('cecoco:importar-dia-anterior');
            })
            ->onFailure(function () {
                app(TelegramService::class)->notificarScheduleFallido('cecoco:importar-dia-anterior', 'El comando finalizó con error.');
            });

        // Geocodifica en lotes las direcciones del día anterior para el mapa de calor.
        // Se ejecuta 30 min después del import para que el job de procesamiento ya haya terminado.
        $schedule->command('cecoco:geocodificar-dia-anterior')->dailyAt('06:30')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/cecoco_geocodificacion.log'))
            ->onFailure(function () {
                app(TelegramService::class)->notificarScheduleFallido('cecoco:geocodificar-dia-anterior', 'El comando finalizó con error.');
            });

        // Pre-trae y guarda el detalle completo (acciones/recursos/cierre) de los eventos
        // del día anterior. Corre después del import (06:00) reutilizando una sola sesión.
        $schedule->command('cecoco:prefetch-detalles')->dailyAt('06:45')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/cecoco_prefetch_detalles.log'))
            ->onSuccess(function () {
                app(TelegramService::class)->notificarScheduleCompletado('cecoco:prefetch-detalles');
            })
            ->onFailure(function () {
                app(TelegramService::class)->notificarScheduleFallido('cecoco:prefetch-detalles', 'El comando finalizó con error.');
            });

        // Actualiza el caché diario de efemérides (Argentina / Entre Ríos) desde Wikipedia.
        $schedule->command('efemerides:actualizar')->dailyAt('00:30')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/efemerides.log'))
            ->onFailure(function () {
                app(TelegramService::class)->notificarScheduleFallido('efemerides:actualizar', 'El comando finalizó con error.');
            });

        $schedule->command('telegram:tareas-diarias')->dailyAt('07:00')
            ->onFailure(function () {
                app(TelegramService::class)->notificarScheduleFallido('telegram:tareas-diarias', 'El comando finalizó con error.');
            });

        $schedule->command('telegram:polling')
            ->everyMinute()
            ->withoutOverlapping(2)
            ->appendOutputTo(storage_path('logs/telegram.log'));

        $schedule->command('transcribir:pendientes')
            ->everyMinute()
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/transcripciones.log'));

        $schedule->command('rag:procesar-pendientes')
            ->everyMinute()
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/rag.log'));

        $schedule->command('rag:consultar-pendientes')
            ->everyMinute()
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/rag.log'));

        $schedule->command('callanalysis:pendientes')
            ->everyMinute()
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/callanalysis.log'));

        // Genera en segundo plano los resúmenes IA de eventos encolados desde la pantalla.
        $schedule->command('cecoco:resumir-pendientes')
            ->everyMinute()
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/cecoco_resumen_ia.log'));

        // Pre-calienta el caché de conteos de geocodificación para el dashboard.
        // Se corre en background cada 5 min para que el endpoint nunca haga la query pesada en el request.
        $schedule->call(function () {
            $total    = \DB::table('evento_cecoco')
                ->whereNotNull('direccion')
                ->where('direccion', '!=', '')
                ->where('direccion', '!=', '-')
                ->distinct()
                ->count('direccion');
            $cacheadas = \DB::table('geocodificacion_directa')->count();
            \Illuminate\Support\Facades\Cache::put('dashboard_geo_counts', [$total, $cacheadas], 360);
        })->name('cache-dashboard-geo-counts')->everyFiveMinutes()->withoutOverlapping();

        // Tamaño de la BD de restauraciones de CECOCO: se consulta una vez por hora
        // y se cachea para que el dashboard nunca pegue al servidor remoto en cada poll.
        $schedule->call(function () {
            try {
                app(\App\Services\CecocoExpedienteService::class)
                    ->actualizarCacheTamanoBaseRestauraciones();
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('No se pudo actualizar tamaño BD restauraciones', [
                    'error' => $e->getMessage(),
                ]);
            }
        })->name('cache-cecoco-tamano-restauraciones')->hourly()->withoutOverlapping();

        $schedule->call(function () {
            try {
                app(\App\Services\CecocoExpedienteService::class)
                    ->actualizarCacheTamanoBaseRestauracionesGps();
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('No se pudo actualizar tamaño BD restauraciones GPS', [
                    'error' => $e->getMessage(),
                ]);
            }
        })->name('cache-cecoco-gps-tamano-restauraciones')->hourly()->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
