<?php

namespace App\Services;

use App\Models\EntregaBodycam;
use App\Models\EntregaEquipo;
use App\Models\EventoCecoco;
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
            Log::channel('telegram')->warning('Telegram: TELEGRAM_BOT_TOKEN no configurado.');
            return false;
        }

        $destino = $chatId ?? $this->chatId;

        if (empty($destino)) {
            Log::channel('telegram')->warning('Telegram: TELEGRAM_CHAT_ID no configurado.');
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
                Log::channel('telegram')->error('Telegram: respuesta no OK', ['body' => $body]);
                return false;
            }

            return true;
        } catch (\Exception $e) {
            $mensajeError = preg_replace('/bot[\w\-:]+\//', 'bot[REDACTED]/', $e->getMessage());
            Log::channel('telegram')->error('Telegram: error al enviar mensaje', [
                'error' => $mensajeError,
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
            $mensajeError = preg_replace('/bot[\w\-:]+\//', 'bot[REDACTED]/', $e->getMessage());
            // Los timeouts de red son esperables; se loguean como warning para no saturar el log
            $nivel = str_contains($e->getMessage(), 'cURL error 28') ? 'warning' : 'error';
            Log::channel('telegram')->$nivel('Telegram: error en getUpdates', ['error' => $mensajeError]);
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

        Log::channel('telegram')->info('Telegram: chat_id detectado', [
            'chat_id'    => $chatId,
            'from'       => $nombre,
            'username'   => $message['from']['username'] ?? null,
            'chat_type'  => $message['chat']['type'] ?? null,
            'chat_title' => $message['chat']['title'] ?? null,
        ]);

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

        if ($this->contieneAlguno($texto, ['cecoco', 'expediente', 'buscar evento', 'evento'])) {
            $termino = $this->extraerTerminoBusqueda($texto, ['cecoco', 'buscar evento', 'expediente', 'evento']);
            $this->responderEventoCecoco($chatId, $termino);
            return;
        }

        $this->enviarMensaje(
            "🤔 No entendí tu consulta, <b>{$nombre}</b>.\n\n"
            . "Probá con:\n"
            . "📋 <b>tareas</b> - Ver tareas pendientes\n"
            . "📊 <b>novedades</b> - Resumen del dashboard\n"
            . "🚨 <b>cecoco 3843583</b> - Buscar evento CECOCO\n"
            . "❓ <b>/ayuda</b> - Ver todos los comandos",
            $chatId
        );
    }

    private function extraerTerminoBusqueda(string $texto, array $prefijos): string
    {
        foreach ($prefijos as $prefijo) {
            $pos = mb_strpos($texto, $prefijo);
            if ($pos !== false) {
                $termino = mb_substr($texto, $pos + mb_strlen($prefijo));
                return trim($termino);
            }
        }
        return trim($texto);
    }

    /**
     * Parsea el formato estructurado con comas:
     * cecoco nro_evento, telefono, fecha, tipo, descripcion
     * Devuelve null si el termino no tiene el formato esperado (sin comas).
     */
    private function parsearConsultaCecoco(string $termino): ?array
    {
        if (!str_contains($termino, ',')) {
            return null;
        }

        // Dividir en exactamente 6 campos (el 6º absorbe posibles comas extras en la descripción)
        $partes = array_pad(array_map('trim', explode(',', $termino, 6)), 6, '');

        [$expediente, $telefono, $fechaStr, $direccion, $tipo, $descripcion] = $partes;

        $resultado = [
            'expediente' => $expediente  !== '' ? $expediente  : null,
            'telefono'   => $telefono    !== '' ? preg_replace('/[^0-9]/', '', $telefono) ?: null : null,
            'fecha'      => null,
            'direccion'  => $direccion   !== '' ? $direccion   : null,
            'tipo_kw'    => $tipo        !== '' ? $tipo        : null,
            'desc_kw'    => $descripcion !== '' ? $descripcion : null,
            'labels'     => [],
        ];

        // Parsear fecha en lenguaje natural
        if ($fechaStr !== '') {
            $resultado['fecha'] = $this->parsearFecha($fechaStr);
        }

        // Construir labels de lo que se va a buscar
        $nombres = ['expediente', 'teléfono', 'fecha', 'dirección', 'tipo', 'descripción'];
        $valores = [
            $resultado['expediente'],
            $resultado['telefono'],
            $resultado['fecha'] ? $resultado['fecha']->format('d/m/Y') : null,
            $resultado['direccion'],
            $resultado['tipo_kw'],
            $resultado['desc_kw'],
        ];
        foreach ($valores as $i => $v) {
            if ($v !== null) {
                $resultado['labels'][] = "{$nombres[$i]}: {$v}";
            }
        }

        return $resultado;
    }

    private function parsearFecha(string $texto): ?Carbon
    {
        $texto = mb_strtolower(trim($texto));

        if (preg_match('/(\d{1,2})[\/\-](\d{1,2})(?:[\/\-](\d{4}))?/', $texto, $m)) {
            $anio = !empty($m[3]) ? (int) $m[3] : now()->year;
            try { return Carbon::createFromDate($anio, (int) $m[2], (int) $m[1]); } catch (\Exception $e) {}
        }
        if (str_contains($texto, 'anteayer') || str_contains($texto, 'antier')) return Carbon::today()->subDays(2);
        if (str_contains($texto, 'ayer'))  return Carbon::yesterday();
        if (str_contains($texto, 'hoy'))   return Carbon::today();

        $diasSemana = [
            'lunes' => 1, 'martes' => 2, 'miercoles' => 3, 'miércoles' => 3,
            'jueves' => 4, 'viernes' => 5, 'sabado' => 6, 'sábado' => 6, 'domingo' => 7,
        ];
        foreach ($diasSemana as $nombre => $dow) {
            if (str_contains($texto, $nombre)) {
                $hoy = Carbon::today();
                $diasAtras = ($hoy->dayOfWeekIso - $dow + 7) % 7;
                if ($diasAtras === 0) $diasAtras = 7;
                return $hoy->subDays($diasAtras);
            }
        }

        return null;
    }

    private function responderEventoCecoco(string $chatId, string $termino): void
    {
        $guia = "🚨 <b>Buscar en CECOCO</b>\n\n"
            . "Enviá los campos separados por comas:\n"
            . "<code>cecoco nro_evento, telefono, fecha, direccion, tipo, descripcion</code>\n\n"
            . "Dejá vacío lo que no necesités:\n"
            . "• <code>cecoco 3843583, , , , ,</code>\n"
            . "• <code>cecoco , 3435258158, , , ,</code>\n"
            . "• <code>cecoco , , hoy, , robo,</code>\n"
            . "• <code>cecoco , , ayer, belgrano, ,</code>\n"
            . "• <code>cecoco , , ayer, , , persona herida</code>\n"
            . "• <code>cecoco , , lunes, , robo, herida</code>";

        if (empty($termino)) {
            $this->enviarMensaje($guia, $chatId);
            return;
        }

        $filtros = $this->parsearConsultaCecoco($termino);

        if ($filtros === null) {
            $this->enviarMensaje("⚠️ Formato incorrecto.\n\n" . $guia, $chatId);
            return;
        }

        try {
            $query     = EventoCecoco::query()->orderBy('fecha_hora', 'desc');
            $hayFiltro = false;

            if ($filtros['expediente']) {
                $query->where('nro_expediente', 'LIKE', "%{$filtros['expediente']}%");
                $hayFiltro = true;
            }

            if ($filtros['fecha']) {
                $query->whereDate('fecha_hora', $filtros['fecha']->toDateString());
                $hayFiltro = true;
            }

            if ($filtros['telefono']) {
                $query->where('telefono', 'LIKE', "%{$filtros['telefono']}%");
                $hayFiltro = true;
            }

            if ($filtros['direccion']) {
                $query->where('direccion', 'LIKE', "%{$filtros['direccion']}%");
                $hayFiltro = true;
            }

            if ($filtros['tipo_kw']) {
                $query->where('tipo_servicio', 'LIKE', "%{$filtros['tipo_kw']}%");
                $hayFiltro = true;
            }

            if ($filtros['desc_kw']) {
                $query->where('descripcion', 'LIKE', "%{$filtros['desc_kw']}%");
                $hayFiltro = true;
            }

            if (!$hayFiltro) {
                $this->enviarMensaje("⚠️ No ingresaste ningún campo de búsqueda.\n\n" . $guia, $chatId);
                return;
            }

            $total = (clone $query)->count();

            if ($total === 0) {
                $resumen = !empty($filtros['labels']) ? implode(', ', $filtros['labels']) : htmlspecialchars($termino);
                $this->enviarMensaje(
                    "🔍 No se encontraron eventos CECOCO para: <b>{$resumen}</b>",
                    $chatId
                );
                return;
            }

            $eventos = $query->limit(5)->get();

            $resumen = !empty($filtros['labels']) ? implode(' | ', $filtros['labels']) : htmlspecialchars($termino);
            $mensaje  = "🚨 <b>Eventos CECOCO</b>\n";
            $mensaje .= "🔎 <i>{$resumen}</i>\n";
            $mensaje .= "Mostrando " . $eventos->count() . " de {$total} resultado(s)\n";
            $mensaje .= "━━━━━━━━━━━━━━━━━━\n";

            foreach ($eventos as $evento) {
                $fecha = $evento->fecha_hora ? $evento->fecha_hora->format('d/m/Y') : '—';
                $hora  = $evento->fecha_hora ? $evento->fecha_hora->format('H:i') : '—';
                $tel   = $evento->telefono ?: '—';
                $dir   = $evento->direccion ?: '—';
                $desc  = $evento->descripcion ? mb_substr($evento->descripcion, 0, 120) : '—';
                $tipo  = $evento->tipo_servicio ?: '—';

                $mensaje .= "\n📋 Expediente: <b>{$evento->nro_expediente}</b>\n";
                $mensaje .= "📅 Fecha: {$fecha}  🕐 Hora: {$hora}\n";
                $mensaje .= "📞 Teléfono: <code>{$tel}</code>\n";
                $mensaje .= "📍 Dirección: {$dir}\n";
                $mensaje .= "🏷 Tipo: {$tipo}\n";
                if ($desc !== '—') {
                    $mensaje .= "📝 Descripción: {$desc}\n";
                }
                $mensaje .= "━━━━━━━━━━━━━━━━━━\n";
            }

            if ($total > 5) {
                $mensaje .= "\n💡 Hay " . ($total - 5) . " resultado(s) más. Agregá más detalles para acotar.";
            }

            $this->enviarMensaje($mensaje, $chatId);
        } catch (\Exception $e) {
            Log::channel('telegram')->error('Telegram: error buscando eventos CECOCO', ['error' => $e->getMessage()]);
            $this->enviarMensaje('❌ Error al buscar eventos CECOCO: ' . $e->getMessage(), $chatId);
        }
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
            . "Soy el bot del <b>Dashboard de Gestión</b>.\n\n"
            . "📋 <b>tareas</b> / <b>pendientes</b>\n"
            . "   → Tareas de hoy y mañana\n\n"
            . "📊 <b>novedades</b> / <b>resumen</b>\n"
            . "   → Equipos, bodycams y entregas\n\n"
            . "🚨 <b>cecoco</b> — Buscar eventos:\n"
            . "   Formato: <code>cecoco nro, tel, fecha, dir, tipo, desc</code>\n"
            . "   • <code>cecoco 3843583, , , , ,</code>\n"
            . "   • <code>cecoco , 3435258158, , , ,</code>\n"
            . "   • <code>cecoco , , hoy, belgrano, robo,</code>\n"
            . "   Enviá <code>cecoco</code> solo para ver todos los ejemplos\n\n"
            . "❓ <b>/ayuda</b> → Este mensaje\n"
            . "   <code>cecoco</code> sin texto → Ver todos los formatos";

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
            Log::channel('telegram')->error('Telegram: error respondiendo tareas', ['error' => $e->getMessage()]);
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
            Log::channel('telegram')->error('Telegram: error respondiendo novedades', ['error' => $e->getMessage()]);
            $this->enviarMensaje('❌ Error al consultar novedades: ' . $e->getMessage(), $chatId);
        }
    }
}
