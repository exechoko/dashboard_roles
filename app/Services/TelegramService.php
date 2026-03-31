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

        if ($this->contieneAlguno($texto, ['cecoco', 'expediente', 'buscar evento'])) {
            $termino = $this->extraerTerminoBusqueda($texto, ['cecoco', 'expediente', 'buscar evento']);
            $this->responderEventoCecoco($chatId, $termino);
            return;
        }

        $this->enviarMensaje(
            "🤔 No entendí tu consulta, <b>{$nombre}</b>.\n\n"
            . "Probá con:\n"
            . "📋 <b>tareas</b> - Ver tareas pendientes\n"
            . "📊 <b>novedades</b> - Resumen del dashboard\n"
            . "🚨 <b>cecoco 1234</b> - Buscar evento por nro, teléfono o dirección\n"
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

    private function parsearConsultaCecoco(string $termino): array
    {
        $resultado = ['fecha' => null, 'telefono' => null, 'direccion' => null, 'keywords' => null, 'labels' => []];

        // Fecha explícita dd/mm o dd/mm/yyyy (con / o -)
        if (preg_match('/(\d{1,2})[\/\-](\d{1,2})(?:[\/\-](\d{4}))?/', $termino, $m)) {
            $anio = !empty($m[3]) ? (int) $m[3] : now()->year;
            $resultado['fecha'] = Carbon::createFromDate($anio, (int) $m[2], (int) $m[1]);
            $resultado['labels'][] = $resultado['fecha']->format('d/m/Y');
            $termino = trim(preg_replace('/\d{1,2}[\/\-]\d{1,2}(?:[\/\-]\d{4})?/', '', $termino));
        } elseif (preg_match('/\bayer\b/', $termino)) {
            $resultado['fecha'] = Carbon::yesterday();
            $resultado['labels'][] = 'ayer';
            $termino = trim(str_replace('ayer', '', $termino));
        } elseif (preg_match('/\bhoy\b/', $termino)) {
            $resultado['fecha'] = Carbon::today();
            $resultado['labels'][] = 'hoy';
            $termino = trim(str_replace('hoy', '', $termino));
        } elseif (preg_match('/\b(ante\s?ayer|antier)\b/i', $termino)) {
            $resultado['fecha'] = Carbon::today()->subDays(2);
            $resultado['labels'][] = 'antier';
            $termino = trim(preg_replace('/\b(ante\s?ayer|antier)\b/i', '', $termino));
        } else {
            $diasSemana = [
                'lunes' => 1, 'martes' => 2, 'miercoles' => 3, 'miércoles' => 3,
                'jueves' => 4, 'viernes' => 5, 'sabado' => 6, 'sábado' => 6, 'domingo' => 7,
            ];
            foreach ($diasSemana as $nombre => $dow) {
                if (mb_strpos($termino, $nombre) !== false) {
                    $hoy = Carbon::today();
                    $diasAtras = ($hoy->dayOfWeekIso - $dow + 7) % 7;
                    if ($diasAtras === 0) {
                        $diasAtras = 7;
                    }
                    $resultado['fecha'] = $hoy->copy()->subDays($diasAtras);
                    $resultado['labels'][] = "el {$nombre}";
                    $termino = trim(str_replace($nombre, '', $termino));
                    break;
                }
            }
        }

        // Teléfono: secuencia de 7+ dígitos (fuera de un patrón de fecha ya extraído)
        if (preg_match('/\b(\d{7,})\b/', $termino, $m)) {
            $resultado['telefono'] = $m[1];
            $resultado['labels'][] = "tel: {$m[1]}";
            $termino = trim(str_replace($m[0], '', $termino));
        }

        // Dirección: texto después de "calle", "av", "avenida", "bv", "boulevard", "pasaje", "esquina"
        if (preg_match('/\b(?:calle|avda?\.?|avenida|bv\.?|boulevard|pasaje|esquina)\s+([\w\s]+?)(?=\s+(?:y|con|entre|el|la|ayer|hoy|lunes|martes|miercoles|jueves|viernes|sabado|domingo|numero|nro|\d)|$)/i', $termino, $m)) {
            $resultado['direccion'] = trim($m[1]);
            $resultado['labels'][] = "dir: {$resultado['direccion']}";
            $termino = trim(preg_replace('/\b(?:calle|avda?\.?|avenida|bv\.?|boulevard|pasaje|esquina)\s+[\w\s]+/i', '', $termino));
        }

        // Keywords residuales: limpiar stopwords
        $stopwords = [
            'cecoco', 'expediente', 'evento', 'hay', 'hubo', 'ocurrio', 'ocurrió', 'relacionado',
            'algun', 'algún', 'alguna', 'una', 'uno', 'este', 'esta', 'ese', 'esa',
            'el', 'la', 'los', 'las', 'un', 'del', 'numero', 'número', 'nro', 'n°',
            'llamo', 'llamó', 'con', 'para', 'que', 'de', 'en', 'por', 'al', 'a',
            'se', 'si', 'lo', 'le', 'les', 'algo', 'podes', 'podés', 'buscar',
            'encontrar', 'pasado', 'pasada', 'ultimo', 'último', 'dia', 'día',
            'noche', 'madrugada', 'tarde', 'mañana', 'fue', 'fui', 'ver', 'saber',
        ];
        $palabras = preg_split('/\s+/', trim($termino));
        $palabras = array_values(array_filter($palabras, function ($p) use ($stopwords) {
            return !in_array(mb_strtolower($p), $stopwords) && mb_strlen(trim($p)) > 2;
        }));
        if (!empty($palabras)) {
            $resultado['keywords'] = implode(' ', $palabras);
            $resultado['labels'][] = "tema: {$resultado['keywords']}";
        }

        return $resultado;
    }

    private function responderEventoCecoco(string $chatId, string $termino): void
    {
        if (empty($termino)) {
            $this->enviarMensaje(
                "🚨 <b>Buscar evento CECOCO</b>\n\n"
                . "Podés preguntarme en lenguaje natural:\n"
                . "• <code>cecoco el número 2994123456 llamó ayer</code>\n"
                . "• <code>cecoco robo en calle belgrano el lunes</code>\n"
                . "• <code>cecoco algo en av alvear el 15/03</code>\n"
                . "• <code>cecoco expediente 12345</code>\n"
                . "• <code>cecoco disturbio hoy</code>",
                $chatId
            );
            return;
        }

        try {
            $filtros = $this->parsearConsultaCecoco($termino);
            $query   = EventoCecoco::query()->orderBy('fecha_hora', 'desc');
            $hayFiltro = false;

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

            if ($filtros['keywords']) {
                $kw = $filtros['keywords'];
                $query->where(function ($q) use ($kw) {
                    $q->where('descripcion', 'LIKE', "%{$kw}%")
                      ->orWhere('tipo_servicio', 'LIKE', "%{$kw}%")
                      ->orWhere('nro_expediente', 'LIKE', "%{$kw}%")
                      ->orWhere('operador', 'LIKE', "%{$kw}%");
                });
                $hayFiltro = true;
            }

            // Si no se extrajo ningún filtro estructurado, usar el scope genérico
            if (!$hayFiltro) {
                $query->buscar($termino);
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
            Log::error('Telegram: error buscando eventos CECOCO', ['error' => $e->getMessage()]);
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
            . "Soy el bot del <b>Dashboard de Gestión</b>.\n"
            . "Podés preguntarme lo siguiente:\n\n"
            . "📋 <b>tareas</b> / <b>pendientes</b>\n"
            . "   → Tareas de hoy y mañana\n\n"
            . "📊 <b>novedades</b> / <b>resumen</b>\n"
            . "   → Equipos, bodycams y entregas\n\n"
            . "🚨 <b>cecoco</b> [consulta libre]\n"
            . "   → Buscar evento en lenguaje natural:\n"
            . "   • <code>cecoco el número 2994123456 llamó ayer</code>\n"
            . "   • <code>cecoco robo en calle belgrano el lunes</code>\n"
            . "   • <code>cecoco disturbio hoy</code>\n"
            . "   • <code>cecoco expediente 12345</code>\n\n"
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
