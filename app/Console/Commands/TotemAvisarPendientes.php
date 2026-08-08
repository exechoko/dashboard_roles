<?php

namespace App\Console\Commands;

use App\Models\ActivacionTotem;
use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

class TotemAvisarPendientes extends Command
{
    protected $signature = 'totem:avisar-pendientes';

    protected $description = 'Avisa por Telegram si hay activaciones de tótem pendientes de descarga o vencidas por el plazo legal de 6 meses.';

    public function handle(TelegramService $telegram): int
    {
        $pendientes = ActivacionTotem::with('camara')
            ->where('estado', ActivacionTotem::ESTADO_PENDIENTE)
            ->orderBy('fecha_evento')
            ->get();

        $vencidas = ActivacionTotem::with('camara')
            ->vencidas()
            ->orderBy('fecha_evento')
            ->get();

        if ($pendientes->isEmpty() && $vencidas->isEmpty()) {
            $this->info('✅ Sin activaciones de tótem pendientes ni vencidas.');

            return Command::SUCCESS;
        }

        $telegram->enviarMensaje($this->mensaje($pendientes, $vencidas));

        $this->info(sprintf(
            '📨 Aviso enviado por Telegram: %d pendiente(s), %d vencida(s).',
            $pendientes->count(),
            $vencidas->count()
        ));

        return Command::SUCCESS;
    }

    private function mensaje(Collection $pendientes, Collection $vencidas): string
    {
        $mensaje = "🎥 <b>Activaciones de Tótem</b>\n"
            . '🕐 ' . now()->format('d/m/Y H:i') . "\n";

        if ($pendientes->isNotEmpty()) {
            $mensaje .= "\n⏳ <b>Pendientes de descarga</b> ({$pendientes->count()})\n";
            $mensaje .= $this->listado($pendientes);
        }

        if ($vencidas->isNotEmpty()) {
            $mensaje .= "\n🚨 <b>Vencidas (plazo legal de " . ActivacionTotem::MESES_RETENCION_LEGAL . " meses)</b> ({$vencidas->count()})\n";
            $mensaje .= $this->listado($vencidas);
        }

        return $mensaje;
    }

    private function listado(Collection $activaciones): string
    {
        $texto = '';

        foreach ($activaciones->take(15) as $activacion) {
            $fecha  = $activacion->fecha_evento?->format('d/m/Y H:i') ?? '—';
            $camara = $activacion->camara->nombre ?? '—';
            $texto .= "\n• <b>{$activacion->nro_expediente}</b> — {$fecha} — {$camara}";
        }

        if ($activaciones->count() > 15) {
            $texto .= "\n… y " . ($activaciones->count() - 15) . ' más';
        }

        return $texto . "\n";
    }
}
