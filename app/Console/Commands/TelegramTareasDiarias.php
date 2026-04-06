<?php

namespace App\Console\Commands;

use App\Models\EntregaBodycam;
use App\Models\EntregaEquipo;
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

        // Entregas activas de equipos
        $entregasEquipos = EntregaEquipo::with(['equipos', 'devoluciones.equipos'])
            ->whereIn('estado', ['entregado', 'devolucion_parcial'])
            ->orderBy('fecha_entrega', 'desc')
            ->get();

        // Entregas activas de bodycams
        $entregasBodycams = EntregaBodycam::with(['bodycams', 'devoluciones.bodycams'])
            ->whereIn('estado', [EntregaBodycam::ESTADO_ENTREGADA, EntregaBodycam::ESTADO_PARCIALMENTE_DEVUELTA])
            ->orderBy('fecha_entrega', 'desc')
            ->get();

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

        $mensaje = "📋 <b>Resumen de Tareas Diarias</b>\n"
            . "📅 {$hoy->format('d/m/Y')} - {$hoy->locale('es')->isoFormat('dddd')}\n";

        // ── Sección Novedades ──────────────────────────────────────
        $mensaje .= "\n━━━━━━━━━━━━━━━━━━\n";
        $mensaje .= "🔔 <b>NOVEDADES</b>\n";
        $mensaje .= "━━━━━━━━━━━━━━━━━━\n";

        // Equipos entregados
        $totalEquiposActivos = 0;
        foreach ($entregasEquipos as $e) {
            $devueltos = $e->devoluciones()->with('equipos')->get()
                ->pluck('equipos')->flatten()->pluck('id')->unique()->count();
            $totalEquiposActivos += ($e->equipos->count() - $devueltos);
        }

        $totalBodycamsActivas = 0;
        foreach ($entregasBodycams as $e) {
            $devueltas = $e->devoluciones()->with('bodycams')->get()
                ->pluck('bodycams')->flatten()->pluck('id')->unique()->count();
            $totalBodycamsActivas += ($e->bodycams->count() - $devueltas);
        }

        $mensaje .= "\n📦 <b>Equipos entregados:</b> {$totalEquiposActivos}\n";
        if ($entregasEquipos->isNotEmpty()) {
            foreach ($entregasEquipos as $e) {
                $devueltos = $e->devoluciones()->with('equipos')->get()
                    ->pluck('equipos')->flatten()->pluck('id')->unique()->count();
                $pendientes = $e->equipos->count() - $devueltos;
                if ($pendientes <= 0) continue;
                $fecha = Carbon::parse($e->fecha_entrega)->format('d/m/Y');
                $receptor = $e->personal_receptor ?? 'Sin datos';
                $dep = $e->dependencia ?? '—';
                $mensaje .= "   • {$pendientes} equipo(s) → <b>{$receptor}</b> ({$dep}) — {$fecha}\n";
            }
        }

        $mensaje .= "\n📷 <b>Bodycams entregadas:</b> {$totalBodycamsActivas}\n";
        if ($entregasBodycams->isNotEmpty()) {
            foreach ($entregasBodycams as $e) {
                $devueltas = $e->devoluciones()->with('bodycams')->get()
                    ->pluck('bodycams')->flatten()->pluck('id')->unique()->count();
                $pendientes = $e->bodycams->count() - $devueltas;
                if ($pendientes <= 0) continue;
                $fecha = Carbon::parse($e->fecha_entrega)->format('d/m/Y');
                $receptor = $e->personal_receptor ?? 'Sin datos';
                $dep = $e->dependencia ?? '—';
                $mensaje .= "   • {$pendientes} bodycam(s) → <b>{$receptor}</b> ({$dep}) — {$fecha}\n";
            }
        }

        // ── Tareas ────────────────────────────────────────────────
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
