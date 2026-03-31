<?php

namespace App\Services;

use App\Models\EntregaBodycam;
use App\Models\EntregaEquipo;
use App\Models\TareaItem;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    protected $botToken;
    protected $chatId;
    protected $client;

    private const API_BASE = 'https://api.telegram.org/bot';

    public function __construct()
    {
        $this->botToken = config('services.telegram.bot_token');
        $this->chatId = config('services.telegram.chat_id');
        $this->client = new Client(['timeout' => 10]);
    }

    public function enviarMensaje(string $mensaje, ?string $chatId = null): bool
    {
        if (empty($this->botToken)) {
            Log::warning('Telegram: TELEGRAM_BOT_TOKEN no configurado.');
            return false;
        }

        $destino = $chatId ?? $this->chatId;

        if (empty($destino)) {
            Log::warning('Telegram: TELEGRAM_CHAT_ID no configurado.');
            return false;
        }

        try {
            $url = self::API_BASE . $this->botToken . '/sendMessage';

            $response = $this->client->post($url, [
                'json' => [
                    'chat_id' => $destino,
                    'text' => $mensaje,
                    'parse_mode' => 'HTML',
                    'disable_web_page_preview' => true,
                ],
            ]);

            $body = json_decode((string) $response->getBody(), true);

            if (!($body['ok'] ?? false)) {
                Log::error('Telegram: respuesta no OK', ['body' => $body]);
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Telegram: error al enviar mensaje', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function notificarJobCompletado(string $jobClass, ?string $detalle = null): bool
    {
        $nombre = class_basename($jobClass);
        $fecha = now()->format('d/m/Y H:i:s');

        $mensaje = "✅ <b>Queue Job Completado</b>\n"
            . "📋 Job: <code>{$nombre}</code>\n"
            . "🕐 Fecha: {$fecha}";

        if ($detalle) {
            $mensaje .= "\n📝 {$detalle}";
        }

        return $this->enviarMensaje($mensaje);
    }

    public function notificarJobFallido(string $jobClass, string $error): bool
    {
        $nombre = class_basename($jobClass);
        $fecha = now()->format('d/m/Y H:i:s');

        $mensaje = "❌ <b>Queue Job Fallido</b>\n"
            . "📋 Job: <code>{$nombre}</code>\n"
            . "🕐 Fecha: {$fecha}\n"
            . "⚠️ Error: {$error}";

        return $this->enviarMensaje($mensaje);
    }

    public function notificarScheduleCompletado(string $comando, string $salida = ''): bool
    {
        $fecha = now()->format('d/m/Y H:i:s');

        $mensaje = "✅ <b>Tarea Programada Completada</b>\n"
            . "📋 Comando: <code>{$comando}</code>\n"
            . "🕐 Fecha: {$fecha}";

        if (!empty($salida)) {
            $salidaCorta = mb_substr($salida, 0, 500);
            $mensaje .= "\n📝 Salida: {$salidaCorta}";
        }

        return $this->enviarMensaje($mensaje);
    }

    public function notificarScheduleFallido(string $comando, string $error): bool
    {
        $fecha = now()->format('d/m/Y H:i:s');

        $mensaje = "❌ <b>Tarea Programada Fallida</b>\n"
            . "📋 Comando: <code>{$comando}</code>\n"
            . "🕐 Fecha: {$fecha}\n"
            . "⚠️ Error: {$error}";

        return $this->enviarMensaje($mensaje);
    }

    public function getUpdates(?int $offset = null): array
    {
        if (empty($this->botToken)) {
            return [];
        }

        try {
            $url = self::API_BASE . $this->botToken . '/getUpdates';
            $params = ['timeout' => 5];

            if ($offset !== null) {
                $params['offset'] = $offset;
            }

            $response = $this->client->post($url, ['json' => $params]);
            $body = json_decode((string) $response->getBody(), true);

            if (!($body['ok'] ?? false)) {
                return [];
            }

            return $body['result'] ?? [];
        } catch (\Exception $e) {
            Log::error('Telegram: error en getUpdates', ['error' => $e->getMessage()]);
            return [];
        }
    }

    public function procesarMensaje(array $message): void
    {
        $chatId = (string) ($message['chat']['id'] ?? '');
        $texto = mb_strtolower(trim($message['text'] ?? ''));
        $nombre = $message['from']['first_name'] ?? 'Usuario';

        if (empty($chatId) || empty($texto)) {
            return;
        }

        if ($texto === '/start' || $texto === '/ayuda' || $texto === '/help') {
            $this->responderAyuda($chatId, $nombre);
            return;
        }

        if ($this->contieneAlguno($texto, ['tarea', 'pendiente', 'hacer', 'programada'])) {
            $this->responderTareasPendientes($chatId);
            return;
        }

        if ($this->contieneAlguno($texto, ['novedad', 'resumen', 'dashboard', 'estado', 'equipo', 'bodycam', 'entrega'])) {
            $this->responderNovedades($chatId);
            return;
        }

        $this->enviarMensaje(
            "🤔 No entendí tu consulta, <b>{$nombre}</b>.\n\n"
            . "Probá con:\n"
            . "📋 <b>tareas</b> - Ver tareas pendientes\n"
            . "📊 <b>novedades</b> - Resumen del dashboard\n"
            . "❓ <b>/ayuda</b> - Ver todos los comandos",
            $chatId
        );
    }

    private function contieneAlguno(string $texto, array $palabras): bool
    {
        foreach ($palabras as $palabra) {
            if (mb_strpos($texto, $palabra) !== false) {
                return true;
            }
        }
        return false;
    }

    private function responderAyuda(string $chatId, string $nombre): void
    {
        $mensaje = "👋 ¡Hola <b>{$nombre}</b>!\n\n"
            . "Soy el bot del <b>Dashboard de Gestión</b>.\n"
            . "Podés preguntarme lo siguiente:\n\n"
            . "📋 <b>tareas</b> / <b>pendientes</b>\n"
            . "   → Tareas de hoy y mañana\n\n"
            . "📊 <b>novedades</b> / <b>resumen</b>\n"
            . "   → Equipos, bodycams y entregas\n\n"
            . "❓ <b>/ayuda</b>\n"
            . "   → Este mensaje de ayuda";

        $this->enviarMensaje($mensaje, $chatId);
    }

    private function responderTareasPendientes(string $chatId): void
    {
        try {
            $hoy = Carbon::today();
            $manana = Carbon::tomorrow();

            $tareasEnProceso = TareaItem::with('tarea')
                ->where('estado', TareaItem::ESTADO_EN_PROCESO)
                ->orderBy('fecha_programada', 'asc')
                ->get();

            $tareasHoy = TareaItem::with('tarea')
                ->whereDate('fecha_programada', $hoy)
                ->where('estado', TareaItem::ESTADO_PENDIENTE)
                ->orderBy('id', 'asc')
                ->get();

            $tareasManana = TareaItem::with('tarea')
                ->whereDate('fecha_programada', $manana)
                ->where('estado', TareaItem::ESTADO_PENDIENTE)
                ->orderBy('id', 'asc')
                ->get();

            $mensaje = "📋 <b>Tareas Pendientes</b>\n"
                . "📅 {$hoy->format('d/m/Y')}\n";

            // En proceso
            $mensaje .= "\n🔄 <b>EN PROCESO</b> ({$tareasEnProceso->count()})\n";
            if ($tareasEnProceso->isEmpty()) {
                $mensaje .= "Ninguna.\n";
            } else {
                foreach ($tareasEnProceso as $i => $item) {
                    $nombre = $item->tarea->nombre ?? 'Sin nombre';
                    $fecha = $item->fecha_programada ? $item->fecha_programada->format('d/m') : '';
                    $mensaje .= ($i + 1) . ". {$nombre} ({$fecha})\n";
                }
            }

            // Hoy
            $mensaje .= "\n⏳ <b>HOY</b> ({$tareasHoy->count()})\n";
            if ($tareasHoy->isEmpty()) {
                $mensaje .= "Sin tareas para hoy.\n";
            } else {
                foreach ($tareasHoy as $i => $item) {
                    $nombre = $item->tarea->nombre ?? 'Sin nombre';
                    $mensaje .= ($i + 1) . ". {$nombre}\n";
                }
            }

            // Mañana
            $mensaje .= "\n📌 <b>MAÑANA</b> ({$tareasManana->count()})\n";
            if ($tareasManana->isEmpty()) {
                $mensaje .= "Sin tareas para mañana.\n";
            } else {
                foreach ($tareasManana as $i => $item) {
                    $nombre = $item->tarea->nombre ?? 'Sin nombre';
                    $mensaje .= ($i + 1) . ". {$nombre}\n";
                }
            }

            $total = $tareasEnProceso->count() + $tareasHoy->count() + $tareasManana->count();
            $mensaje .= "\n📊 Total pendientes: <b>{$total}</b>";

            $this->enviarMensaje($mensaje, $chatId);
        } catch (\Exception $e) {
            Log::error('Telegram: error respondiendo tareas', ['error' => $e->getMessage()]);
            $this->enviarMensaje('❌ Error al consultar las tareas: ' . $e->getMessage(), $chatId);
        }
    }

    private function responderNovedades(string $chatId): void
    {
        try {
            $hoy = Carbon::today();
            $manana = Carbon::tomorrow();

            // Entregas activas de equipos
            $entregasEquipos = EntregaEquipo::with(['equipos', 'devoluciones.equipos'])
                ->whereIn('estado', ['entregado', 'devolucion_parcial'])
                ->orderBy('fecha_entrega', 'desc')
                ->get();

            $cantEquiposEntregados = 0;
            $detalleEntregasEquipos = [];
            foreach ($entregasEquipos as $entrega) {
                $devueltos = $entrega->devoluciones->pluck('equipos')->flatten()->pluck('id')->unique()->count();
                $pendientes = $entrega->equipos->count() - $devueltos;
                $cantEquiposEntregados += $pendientes;
                if ($pendientes > 0) {
                    $fecha = $entrega->fecha_entrega ? $entrega->fecha_entrega->format('d/m/Y') : '';
                    $dep = mb_substr($entrega->dependencia ?? '', 0, 25);
                    $detalleEntregasEquipos[] = "  • {$fecha} - {$dep} ({$pendientes} eq.)";
                }
            }

            // Entregas activas de bodycams
            $entregasBodycams = EntregaBodycam::with(['bodycams', 'devoluciones.bodycams'])
                ->whereIn('estado', [EntregaBodycam::ESTADO_ENTREGADA, EntregaBodycam::ESTADO_PARCIALMENTE_DEVUELTA])
                ->orderBy('fecha_entrega', 'desc')
                ->get();

            $cantBodycamsEntregadas = 0;
            $detalleEntregasBodycams = [];
            foreach ($entregasBodycams as $entrega) {
                $devueltas = $entrega->devoluciones->pluck('bodycams')->flatten()->pluck('id')->unique()->count();
                $pendientes = $entrega->bodycams->count() - $devueltas;
                $cantBodycamsEntregadas += $pendientes;
                if ($pendientes > 0) {
                    $fecha = $entrega->fecha_entrega ? $entrega->fecha_entrega->format('d/m/Y') : '';
                    $dep = mb_substr($entrega->dependencia ?? '', 0, 25);
                    $detalleEntregasBodycams[] = "  • {$fecha} - {$dep} ({$pendientes} bc.)";
                }
            }

            // Tareas resumen
            $tareasEnProceso = TareaItem::where('estado', TareaItem::ESTADO_EN_PROCESO)->count();
            $tareasHoy = TareaItem::whereDate('fecha_programada', $hoy)
                ->where('estado', TareaItem::ESTADO_PENDIENTE)->count();
            $tareasManana = TareaItem::whereDate('fecha_programada', $manana)
                ->where('estado', TareaItem::ESTADO_PENDIENTE)->count();

            // Armar mensaje
            $mensaje = "📊 <b>Novedades del Dashboard</b>\n"
                . "📅 {$hoy->format('d/m/Y H:i')}\n";

            $mensaje .= "\n━━━━━━━━━━━━━━━━━━\n";
            $mensaje .= "📡 <b>EQUIPOS ENTREGADOS</b>\n";
            $mensaje .= "Total sin devolver: <b>{$cantEquiposEntregados}</b>\n";
            $mensaje .= "Entregas activas: <b>{$entregasEquipos->count()}</b>\n";
            if (!empty($detalleEntregasEquipos)) {
                $mensaje .= implode("\n", array_slice($detalleEntregasEquipos, 0, 5)) . "\n";
                if (count($detalleEntregasEquipos) > 5) {
                    $mensaje .= "  ... y " . (count($detalleEntregasEquipos) - 5) . " más\n";
                }
            }

            $mensaje .= "\n━━━━━━━━━━━━━━━━━━\n";
            $mensaje .= "📹 <b>BODYCAMS ENTREGADAS</b>\n";
            $mensaje .= "Total sin devolver: <b>{$cantBodycamsEntregadas}</b>\n";
            $mensaje .= "Entregas activas: <b>{$entregasBodycams->count()}</b>\n";
            if (!empty($detalleEntregasBodycams)) {
                $mensaje .= implode("\n", array_slice($detalleEntregasBodycams, 0, 5)) . "\n";
                if (count($detalleEntregasBodycams) > 5) {
                    $mensaje .= "  ... y " . (count($detalleEntregasBodycams) - 5) . " más\n";
                }
            }

            $mensaje .= "\n━━━━━━━━━━━━━━━━━━\n";
            $mensaje .= "📋 <b>TAREAS</b>\n";
            $mensaje .= "🔄 En proceso: <b>{$tareasEnProceso}</b>\n";
            $mensaje .= "⏳ Pendientes hoy: <b>{$tareasHoy}</b>\n";
            $mensaje .= "📌 Pendientes mañana: <b>{$tareasManana}</b>\n";

            $this->enviarMensaje($mensaje, $chatId);
        } catch (\Exception $e) {
            Log::error('Telegram: error respondiendo novedades', ['error' => $e->getMessage()]);
            $this->enviarMensaje('❌ Error al consultar novedades: ' . $e->getMessage(), $chatId);
        }
    }
}
