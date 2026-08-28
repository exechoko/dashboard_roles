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

        $schedule->command('armas:importar-personal911')->dailyAt('05:30')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/armas_personal911.log'));

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

        // Reintenta con Nominatim las direcciones que quedaron sin coordenadas en el
        // caché (backlog de la época en que Google era el único motor). Corre de
        // madrugada en lotes acotados hasta agotar el backlog.
        $schedule->command('cecoco:reintentar-geocodificacion-fallida --limite=10000 --pausa=50')->dailyAt('02:00')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/cecoco_geocodificacion_reintentos.log'))
            ->onFailure(function () {
                app(TelegramService::class)->notificarScheduleFallido('cecoco:reintentar-geocodificacion-fallida', 'El comando finalizó con error.');
            });

        // Detecta .mbox nuevos o modificados en las carpetas de los buzones de correo
        // (Herramientas > Visor de Correos) y encola su indexación. No hace nada mientras
        // MBOX_AUTO_INDEXAR esté apagado (ver config/mbox.php).
        $schedule->command('mbox:detectar-nuevos')->dailyAt('03:00')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/mbox.log'))
            ->onFailure(function () {
                app(TelegramService::class)->notificarScheduleFallido('mbox:detectar-nuevos', 'El comando finalizó con error.');
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

        // Detecta activaciones de tótem BDE en los eventos CECOCO de los últimos 7 días.
        // Corre después del import y el prefetch de detalles.
        $schedule->command('totem:detectar-activaciones')->dailyAt('07:00')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/totem_activaciones.log'))
            ->onFailure(function () {
                app(TelegramService::class)->notificarScheduleFallido('totem:detectar-activaciones', 'El comando finalizó con error.');
            });

        // Avisa por Telegram si quedaron activaciones de tótem pendientes de
        // descarga o vencidas por el plazo legal de 6 meses. Corre después de
        // la detección diaria.
        $schedule->command('totem:avisar-pendientes')->dailyAt('07:10')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/totem_avisar_pendientes.log'))
            ->onFailure(function () {
                app(TelegramService::class)->notificarScheduleFallido('totem:avisar-pendientes', 'El comando finalizó con error.');
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

        // Hashea y copia a la carpeta de red los videos de tótem subidos desde la
        // pantalla, desacoplado del request HTTP que recibió la subida.
        $schedule->command('totem:procesar-videos-pendientes')
            ->everyMinute()
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/totem_procesar_videos.log'));

        // Trae los CDR del dia anterior directamente del panel de la central telefonica
        // (SSW), sin depender de la exportacion/carga manual de CSV. Mismo mecanismo que
        // cecoco:importar-dia-anterior. El dia en curso se trae a demanda con el boton
        // "Importar hoy" en Importar > Llamadas central telefonica.
        $schedule->command('cecoco:sincronizar-llamadas-central-telefonica')->dailyAt('06:15')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/central_telefonica_sincronizacion.log'))
            ->onSuccess(function () {
                app(TelegramService::class)->notificarScheduleCompletado('cecoco:sincronizar-llamadas-central-telefonica');
            })
            ->onFailure(function () {
                app(TelegramService::class)->notificarScheduleFallido('cecoco:sincronizar-llamadas-central-telefonica', 'El comando finalizó con error.');
            });

        // Vigila en LibreNMS el uso de CPU de las PCs de los operadores de video
        // (grupo CCTV) y alerta por Telegram cuando un equipo supera el umbral.
        $schedule->command('librenms:monitorear-cpu')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/librenms_cpu.log'));

        // Estado de las +300 cámaras 911 en LibreNMS: cachea total y caídas
        // (con hace cuánto no responden) para el dashboard y el bot.
        $schedule->command('librenms:monitorear-camaras')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/librenms_camaras.log'));

        // Estado de los troncales SIP de la central telefonica (panel SSW): cachea
        // para la card del dashboard y avisa por Telegram si alguno cae o se
        // recupera (riesgo de corte de comunicaciones del 911).
        $schedule->command('central-telefonica:monitorear-troncales')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/central_telefonica_troncales.log'));

        // Releva por ping+SNMP las PCs, servidores, cámaras internas y equipos de
        // red del edificio (dispositivos_edificio) y avisa por Telegram cuando
        // alguno cae o supera los umbrales de CPU/RAM/disco.
        $schedule->command('infraestructura:monitorear')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/infraestructura.log'));

        // Avisa por mail si quedaron mensajes de chat sin leer hace más de 30 minutos.
        // No repite el aviso hasta que el usuario vuelva a leer esa conversación.
        $schedule->command('chat:avisar-no-leidos')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/chat_avisar_no_leidos.log'));

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
        // Se dispara vía el Job (no llamando al servicio directo) para que la
        // alerta de "supera el umbral" corra también acá, igual que cuando se
        // dispara desde el botón "refrescar ahora" de la pantalla Workers.
        $schedule->call(function () {
            \App\Jobs\ConsultarTamanoRestauracionesCecoco::dispatchSync();
        })->name('cache-cecoco-tamano-restauraciones')->hourly()->withoutOverlapping();

        $schedule->call(function () {
            \App\Jobs\ConsultarTamanoRestauracionesCecoco::dispatchSync(true);
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
