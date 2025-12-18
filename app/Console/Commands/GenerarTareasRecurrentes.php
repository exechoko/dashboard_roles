<?php

namespace App\Console\Commands;

use App\Models\Tarea;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerarTareasRecurrentes extends Command
{
    protected $signature = 'tareas:generar {--days=60 : Cantidad de dias hacia adelante a generar}';

    protected $description = 'Genera instancias (tarea_items) para tareas activas y recurrentes.';

    public function handle()
    {
        $days = (int) ($this->option('days') ?? 60);
        if ($days < 1) {
            $days = 60;
        }

        $desde = Carbon::now();
        $hasta = Carbon::now()->addDays($days);

        $tareas = Tarea::where('activa', true)->get();

        $total = 0;
        foreach ($tareas as $tarea) {
            $total += $tarea->generarItems($desde, $hasta);
        }

        $this->info('Items generados/asegurados: ' . $total);

        return 0;
    }
}
