<?php

namespace App\Console\Commands;

use App\Mail\TareasDelDiaMail;
use App\Models\TareaItem;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class AvisarTareasDelDia extends Command
{
    protected $signature = 'tareas:avisar {--fecha= : Fecha en formato Y-m-d (por defecto: hoy)} {--emails= : Lista de emails separados por coma (override de TAREAS_AVISO_EMAILS)}';

    protected $description = 'Envía por mail las tareas programadas para el día (pendiente/en_proceso).';

    public function handle()
    {
        try {
            $fecha = $this->option('fecha')
                ? Carbon::createFromFormat('Y-m-d', $this->option('fecha'))->startOfDay()
                : Carbon::today();
        } catch (\Exception $e) {
            $this->error('Fecha inválida. Use formato Y-m-d. Ejemplo: --fecha=2025-12-18');
            return 1;
        }

        $emailsRaw = $this->option('emails') ?? env('TAREAS_AVISO_EMAILS');
        $emails = collect(explode(',', (string) $emailsRaw))
            ->map(function ($e) {
                return trim($e);
            })
            ->filter()
            ->values()
            ->all();

        if (count($emails) === 0) {
            $this->error('No hay destinatarios configurados. Defina TAREAS_AVISO_EMAILS en el .env (separados por coma).');
            return 1;
        }

        $items = TareaItem::with('tarea')
            ->whereDate('fecha_programada', $fecha->toDateString())
            ->whereIn('estado', [TareaItem::ESTADO_PENDIENTE, TareaItem::ESTADO_EN_PROCESO])
            ->orderBy('fecha_programada', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        if ($items->count() === 0) {
            $this->info('No hay tareas pendientes/en proceso para ' . $fecha->toDateString() . '. No se envía email.');
            return 0;
        }

        $enviados = [];
        $fallidos = [];

        foreach ($emails as $email) {
            try {
                Mail::to($email)->send(new TareasDelDiaMail($fecha, $items));
                $enviados[] = $email;
            } catch (\Exception $e) {
                $fallidos[] = $email;
                $this->error('Error enviando a ' . $email . ': ' . $e->getMessage());
            }
        }

        if (count($enviados) > 0) {
            $this->info('Email enviado a: ' . implode(', ', $enviados));
        }
        if (count($fallidos) > 0) {
            $this->error('No se pudo enviar a: ' . implode(', ', $fallidos));
        }

        $this->info('Tareas incluidas: ' . $items->count());

        if (count($fallidos) > 0) {
            return 1;
        }

        return 0;
    }
}
