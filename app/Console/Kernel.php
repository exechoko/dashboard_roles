<?php

namespace App\Console;

use App\Services\TelegramService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

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

        $schedule->command('telegram:tareas-diarias')->dailyAt('07:00')
            ->onFailure(function () {
                app(TelegramService::class)->notificarScheduleFallido('telegram:tareas-diarias', 'El comando finalizó con error.');
            });

        $schedule->command('telegram:polling')->everyMinute()->withoutOverlapping();

        $schedule->command('transcribir:pendientes')
            ->everyMinute()
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/transcripciones.log'));
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
