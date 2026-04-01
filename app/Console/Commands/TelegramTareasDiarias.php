<?php

namespace App\Console\Commands;

use App\Models\TareaItem;
use App\Services\TelegramService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class TelegramTareasDiarias extends Command
{
    protected $signature = 'telegram:tareas-diarias';

    protected $description = 'Envía por Telegram las tareas de hoy y mañana (pendientes/en proceso).';

    public function handle(TelegramService $telegram): int
    {
        $hoy = Carbon::today();
        $manana = Carbon::tomorrow();

        $tareasHoy = TareaItem::with('tarea')
            ->whereDate('fecha_programada', $hoy->toDateString())
            ->whereIn('estado', [TareaItem::ESTADO_PENDIENTE, TareaItem::ESTADO_EN_PROCESO])
            ->orderBy('fecha_programada', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $tareasManana = TareaItem::with('tarea')
            ->whereDate('fecha_programada', $manana->toDateString())
            ->whereIn('estado', [TareaItem::ESTADO_PENDIENTE, TareaItem::ESTADO_EN_PROCESO])
            ->orderBy('fecha_programada', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        if ($tareasHoy->isEmpty() && $tareasManana->isEmpty()) {
            $mensaje = "📋 <b>Resumen de Tareas</b>\n"
                . "📅 {$hoy->format('d/m/Y')}\n\n"
                . "No hay tareas pendientes para hoy ni mañana.";

            $telegram->enviarMensaje($mensaje);
            $this->info('No hay tareas pendientes. Notificación enviada.');
            return 0;
        }

        $mensaje = "📋 <b>Resumen de Tareas Diarias</b>\n"
            . "📅 {$hoy->format('d/m/Y')} - {$hoy->locale('es')->isoFormat('dddd')}\n";

        $mensaje .= "\n━━━━━━━━━━━━━━━━━━\n";
        $mensaje .= "📌 <b>TAREAS DE HOY</b> ({$tareasHoy->count()})\n";
        $mensaje .= "━━━━━━━━━━━━━━━━━━\n";

        if ($tareasHoy->isEmpty()) {
            $mensaje .= "Sin tareas pendientes para hoy.\n";
        } else {
            foreach ($tareasHoy as $index => $item) {
                $numero = $index + 1;
                $estadoIcon = $item->estado === TareaItem::ESTADO_EN_PROCESO ? '🔄' : '⏳';
                $estadoTexto = TareaItem::ESTADOS[$item->estado] ?? $item->estado;
                $nombre = $item->tarea->nombre ?? 'Sin nombre';

                $mensaje .= "\n{$numero}. {$estadoIcon} <b>{$nombre}</b>\n";
                $mensaje .= "   Estado: {$estadoTexto}\n";

                if ($item->tarea && $item->tarea->descripcion) {
                    $desc = mb_substr($item->tarea->descripcion, 0, 100);
                    $mensaje .= "   📝 {$desc}\n";
                }
            }
        }

        $mensaje .= "\n━━━━━━━━━━━━━━━━━━\n";
        $mensaje .= "📌 <b>TAREAS DE MAÑANA</b> ({$tareasManana->count()})\n";
        $mensaje .= "━━━━━━━━━━━━━━━━━━\n";

        if ($tareasManana->isEmpty()) {
            $mensaje .= "Sin tareas programadas para mañana.\n";
        } else {
            foreach ($tareasManana as $index => $item) {
                $numero = $index + 1;
                $estadoIcon = $item->estado === TareaItem::ESTADO_EN_PROCESO ? '🔄' : '⏳';
                $estadoTexto = TareaItem::ESTADOS[$item->estado] ?? $item->estado;
                $nombre = $item->tarea->nombre ?? 'Sin nombre';

                $mensaje .= "\n{$numero}. {$estadoIcon} <b>{$nombre}</b>\n";
                $mensaje .= "   Estado: {$estadoTexto}\n";

                if ($item->tarea && $item->tarea->descripcion) {
                    $desc = mb_substr($item->tarea->descripcion, 0, 100);
                    $mensaje .= "   📝 {$desc}\n";
                }
            }
        }

        $chatIds = collect(explode(',', (string) config('services.telegram.tareas_chat_ids')))
            ->map(fn($id) => trim($id))
            ->filter()
            ->whenEmpty(fn($c) => $c->push(null)) // usa el chat_id por defecto si no hay lista
            ->values();

        $fallidos = 0;
        foreach ($chatIds as $chatId) {
            $enviado = $telegram->enviarMensaje($mensaje, $chatId ?: null);
            if (!$enviado) {
                $fallidos++;
                $this->error('No se pudo enviar a: ' . ($chatId ?: 'chat_id por defecto'));
            }
        }

        if ($fallidos === 0) {
            $this->info("Resumen enviado por Telegram. Hoy: {$tareasHoy->count()}, Mañana: {$tareasManana->count()}");
            return 0;
        }

        return $fallidos < $chatIds->count() ? 0 : 1;
    }
}
