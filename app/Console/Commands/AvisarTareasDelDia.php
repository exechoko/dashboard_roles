<?php

namespace App\Console\Commands;

use App\Mail\TareasDelDiaMail;
use App\Models\TareaItem;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AvisarTareasDelDia extends Command
{
    protected $signature = 'tareas:avisar {--fecha= : Fecha en formato Y-m-d (por defecto: hoy)} {--emails= : Lista de emails separados por coma (override de TAREAS_AVISO_EMAILS)}';

    protected $description = 'Envía por mail las tareas programadas para el día (pendiente/en_proceso).';

    public function handle()
    {
        Log::channel('daily')->info('[tareas:avisar] Inicio del comando.');

        try {
            $fecha = $this->option('fecha')
                ? Carbon::createFromFormat('Y-m-d', $this->option('fecha'))->startOfDay()
                : Carbon::today();
        } catch (\Exception $e) {
            $msg = 'Fecha inválida. Use formato Y-m-d. Ejemplo: --fecha=2025-12-18';
            $this->error($msg);
            Log::channel('daily')->error('[tareas:avisar] ' . $msg, ['exception' => $e->getMessage()]);
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
            $msg = 'No hay destinatarios configurados. Defina TAREAS_AVISO_EMAILS en el .env (separados por coma).';
            $this->error($msg);
            Log::channel('daily')->error('[tareas:avisar] ' . $msg);
            return 1;
        }

        $items = TareaItem::with('tarea')
            ->whereDate('fecha_programada', $fecha->toDateString())
            ->whereIn('estado', [TareaItem::ESTADO_PENDIENTE, TareaItem::ESTADO_EN_PROCESO])
            ->orderBy('fecha_programada', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        if ($items->count() === 0) {
            $msg = 'No hay tareas pendientes/en proceso para ' . $fecha->toDateString() . '. No se envía email.';
            $this->info($msg);
            Log::channel('daily')->info('[tareas:avisar] ' . $msg);
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
                Log::channel('daily')->error('[tareas:avisar] Error enviando a ' . $email, [
                    'exception'  => $e->getMessage(),
                    'trace'      => $e->getTraceAsString(),
                ]);
            }
        }

        if (count($enviados) > 0) {
            $msg = 'Email enviado a: ' . implode(', ', $enviados);
            $this->info($msg);
            Log::channel('daily')->info('[tareas:avisar] ' . $msg, ['tareas_count' => $items->count()]);
        }
        if (count($fallidos) > 0) {
            $msg = 'No se pudo enviar a: ' . implode(', ', $fallidos);
            $this->error($msg);
            Log::channel('daily')->error('[tareas:avisar] ' . $msg);
        }

        $this->info('Tareas incluidas: ' . $items->count());

        if (count($fallidos) > 0) {
            return 1;
        }

        return 0;
    }
}
