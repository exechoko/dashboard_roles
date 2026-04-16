<?php

namespace App\Http\Controllers;

use App\Models\EventoCecoco;
use App\Models\Importacion;
use App\Services\EventoCecocoParser;
use App\Services\CecocoExpedienteService;
use App\Services\CecocoGrabacionesService;
use App\Services\CecocoGrabacionesLocalService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EventoCecocoController extends Controller
{
    private EventoCecocoParser $parser;
    private CecocoExpedienteService $expedienteService;

    public function __construct(EventoCecocoParser $parser, CecocoExpedienteService $expedienteService)
    {
        $this->parser = $parser;
        $this->expedienteService = $expedienteService;
    }

    public function index(Request $request)
    {
        $eventos = null;
        $totalResultados = null;
        $tieneFiltros = $request->hasAny(['anio', 'mes', 'operador', 'tipo', 'desde_datetime', 'hasta_datetime', 'desde', 'hasta', 'buscar']);

        if ($tieneFiltros) {
            $query = EventoCecoco::select([
                'id',
                'nro_expediente',
                'fecha_hora',
                'operador',
                'direccion',
                'telefono',
                'tipo_servicio',
                'periodo',
                'anio',
                'mes'
            ]);

            if ($request->filled('anio')) {
                $query->delAnio((int) $request->anio);
            }

            if ($request->filled('mes')) {
                $query->delMes((int) $request->mes);
            }

            if ($request->filled('operador')) {
                $query->porOperador($request->operador);
            }

            if ($request->filled('tipo')) {
                $query->porTipo($request->tipo);
            }

            if ($request->filled('desde_datetime') && $request->filled('hasta_datetime')) {
                $desdeCompleto = str_replace('T', ' ', $request->input('desde_datetime'));
                $hastaCompleto = str_replace('T', ' ', $request->input('hasta_datetime'));

                if (strlen($desdeCompleto) === 16) {
                    $desdeCompleto .= ':00';
                }

                if (strlen($hastaCompleto) === 16) {
                    $hastaCompleto .= ':59';
                }
                $query->whereBetween('fecha_hora', [$desdeCompleto, $hastaCompleto]);
            } elseif ($request->filled('desde') && $request->filled('hasta')) {
                $desdeCompleto = $request->desde . ' ' . ($request->filled('hora_desde') ? $request->hora_desde : '00:00:00');
                $hastaCompleto = $request->hasta . ' ' . ($request->filled('hora_hasta') ? $request->hora_hasta : '23:59:59');
                $query->whereBetween('fecha_hora', [$desdeCompleto, $hastaCompleto]);
            }

            if ($request->filled('buscar')) {
                $query->buscar($request->buscar);
            }

            $filtrosConteo = [
                'anio' => $request->input('anio'),
                'mes' => $request->input('mes'),
                'operador' => $request->input('operador'),
                'tipo' => $request->input('tipo'),
                'desde_datetime' => $request->input('desde_datetime'),
                'hasta_datetime' => $request->input('hasta_datetime'),
                'desde' => $request->input('desde'),
                'hasta' => $request->input('hasta'),
                'hora_desde' => $request->input('hora_desde'),
                'hora_hasta' => $request->input('hora_hasta'),
                'buscar' => $request->input('buscar'),
            ];

            $cacheKeyConteo = 'cecoco_count_filtros_' . md5(json_encode($filtrosConteo));
            $totalResultados = Cache::remember($cacheKeyConteo, 120, function () use ($query) {
                return (clone $query)->count();
            });

            $eventos = $query->orderBy('fecha_hora', 'desc')->simplePaginate(50)->withQueryString();
        }

        $anios = Cache::rememberForever('cecoco_anios', function () {
            return EventoCecoco::distinct()->orderByDesc('anio')->pluck('anio');
        });

        $tipos = Cache::rememberForever('cecoco_tipos', function () {
            return EventoCecoco::distinct()->orderBy('tipo_servicio')->pluck('tipo_servicio');
        });

        $operadores = Cache::rememberForever('cecoco_operadores', function () {
            return EventoCecoco::distinct()->orderBy('operador')->limit(300)->pluck('operador');
        });

        $meses = [];
        if ($request->filled('anio')) {
            $meses = Cache::rememberForever('cecoco_meses_' . $request->anio, function () use ($request) {
                return EventoCecoco::where('anio', $request->anio)
                    ->distinct()
                    ->orderBy('mes')
                    ->pluck('mes');
            });
        }

        return view('eventos-cecoco.index', compact(
            'eventos',
            'totalResultados',
            'anios',
            'tipos',
            'operadores',
            'meses'
        ));
    }

    public function show(Request $request, EventoCecoco $eventoCecoco)
    {
        $eventoCecoco->load('importacion');
        $filtros = $request->only([
            'anio',
            'mes',
            'operador',
            'tipo',
            'desde_datetime',
            'hasta_datetime',
            'desde',
            'hasta',
            'hora_desde',
            'hora_hasta',
            'buscar',
            'page',
        ]);

        return view('eventos-cecoco.show', compact('eventoCecoco', 'filtros'));
    }

    public function importarForm()
    {
        $importaciones = Importacion::orderByDesc('created_at')->simplePaginate(20);

        $totalArchivosImportados = Cache::remember('cecoco_total_archivos_importados', 300, function () {
            return Importacion::where('estado', 'completado')->count();
        });

        $totalRegistrosEnBd = Cache::rememberForever('cecoco_total_bd', function () {
            return EventoCecoco::count();
        });

        $aniosCounts = Cache::remember('cecoco_importaciones_por_anio', 300, function () {
            return Importacion::where('estado', 'completado')
                ->whereNotNull('anio')
                ->selectRaw('anio, COUNT(*) as total')
                ->groupBy('anio')
                ->orderByDesc('anio')
                ->pluck('total', 'anio');
        });

        return view('eventos-cecoco.importar', compact(
            'importaciones',
            'totalArchivosImportados',
            'totalRegistrosEnBd',
            'aniosCounts'
        ));
    }

    public function importar(Request $request)
    {
        $request->validate([
            'archivos' => 'required|array|min:1',
            'archivos.*' => 'required|file|mimes:xls,xlsx,xml|max:102400',
        ]);

        $archivos = $request->file('archivos');
        $totalArchivos = count($archivos);
        $archivosEncolados = [];

        foreach ($archivos as $archivo) {
            $nombreOriginal = $archivo->getClientOriginalName();

            $importacion = Importacion::create([
                'nombre_archivo' => $nombreOriginal,
                'estado' => 'pendiente',
            ]);

            $archivoPath = $archivo->store('importaciones_temp');

            \App\Jobs\ProcesarArchivoEventoCecoco::dispatch(
                $archivoPath,
                $nombreOriginal,
                $importacion->id
            );

            $archivosEncolados[] = $nombreOriginal;
        }

        Cache::forget('cecoco_total_importaciones');

        $mensaje = "📋 {$totalArchivos} archivo(s) agregado(s) a la cola de procesamiento. ";
        $mensaje .= "Los archivos se procesarán en segundo plano. ";
        $mensaje .= "Puedes ver el progreso en el historial de importaciones.";

        return redirect()->route('cecoco.importar')->with('success', $mensaje);
    }

    public function exportarTxt(Request $request)
    {
        $query = EventoCecoco::query();

        if ($request->filled('anio')) {
            $query->delAnio((int) $request->anio);
        }

        if ($request->filled('mes')) {
            $query->delMes((int) $request->mes);
        }

        if ($request->filled('operador')) {
            $query->porOperador($request->operador);
        }

        if ($request->filled('tipo')) {
            $query->porTipo($request->tipo);
        }

        if ($request->filled('desde_datetime') && $request->filled('hasta_datetime')) {
            $desdeCompleto = str_replace('T', ' ', $request->input('desde_datetime'));
            $hastaCompleto = str_replace('T', ' ', $request->input('hasta_datetime'));

            if (strlen($desdeCompleto) === 16) {
                $desdeCompleto .= ':00';
            }

            if (strlen($hastaCompleto) === 16) {
                $hastaCompleto .= ':59';
            }
            $query->whereBetween('fecha_hora', [$desdeCompleto, $hastaCompleto]);
        } elseif ($request->filled('desde') && $request->filled('hasta')) {
            $desdeCompleto = $request->desde . ' ' . ($request->filled('hora_desde') ? $request->hora_desde : '00:00:00');
            $hastaCompleto = $request->hasta . ' ' . ($request->filled('hora_hasta') ? $request->hora_hasta : '23:59:59');
            $query->whereBetween('fecha_hora', [$desdeCompleto, $hastaCompleto]);
        }

        if ($request->filled('buscar')) {
            $query->buscar($request->buscar);
        }

        $filename = 'cecoco_eventos_' . now()->format('Ymd_His') . '.txt';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');

            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Nº Expediente',
                'Fecha/Hora',
                'Box',
                'Operador',
                'Descripción',
                'Dirección',
                'Teléfono',
                'Fecha Cierre',
                'Tipo Servicio',
                'Período',
                'Año',
                'Mes'
            ], ';');

            $query->orderBy('fecha_hora', 'desc')->chunk(500, function ($eventos) use ($handle) {
                foreach ($eventos as $evento) {
                    fputcsv($handle, [
                        $evento->nro_expediente,
                        $evento->fecha_hora ? $evento->fecha_hora->format('d/m/Y H:i:s') : '',
                        $evento->box,
                        $evento->operador,
                        $evento->descripcion,
                        $evento->direccion,
                        $evento->telefono,
                        $evento->fecha_cierre ? $evento->fecha_cierre->format('d/m/Y H:i:s') : '',
                        $evento->tipo_servicio,
                        $evento->periodo,
                        $evento->anio,
                        $evento->mes,
                    ], ';');
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function apiListar(Request $request)
    {
        $query = EventoCecoco::select([
            'id',
            'nro_expediente',
            'fecha_hora',
            'operador',
            'direccion',
            'telefono',
            'tipo_servicio',
            'periodo'
        ]);

        if ($request->filled('q')) {
            $query->buscar($request->q);
        }

        if ($request->filled('anio')) {
            $query->delAnio((int) $request->anio);
        }

        if ($request->filled('mes')) {
            $query->delMes((int) $request->mes);
        }

        if ($request->filled('tipo')) {
            $query->porTipo($request->tipo);
        }

        $eventos = $query->orderBy('fecha_hora', 'desc')->paginate(100);

        return response()->json($eventos);
    }

    public function verExpediente(Request $request, EventoCecoco $eventoCecoco)
    {
        $this->authorize('ver-expediente-cecoco');

        try {
            $refrescar = $request->query('refrescar', false);
            $detalle = null;

            if (!$refrescar) {
                $cache = \App\Models\DetalleExpedienteCecoco::where('evento_cecoco_id', $eventoCecoco->id)->first();
                if ($cache) {
                    $detalle = $cache->detalle_json;
                }
            }

            if (!$detalle) {
                $detalle = $this->expedienteService->obtenerDetalleExpediente($eventoCecoco->nro_expediente);

                \App\Models\DetalleExpedienteCecoco::updateOrCreate(
                    ['evento_cecoco_id' => $eventoCecoco->id],
                    [
                        'nro_expediente' => $eventoCecoco->nro_expediente,
                        'detalle_json' => $detalle,
                        'fecha_consulta' => now(),
                    ]
                );
            }

            $filtros = $request->only([
                'anio',
                'mes',
                'operador',
                'tipo',
                'desde_datetime',
                'hasta_datetime',
                'desde',
                'hasta',
                'hora_desde',
                'hora_hasta',
                'buscar',
                'page',
            ]);

            return view('eventos-cecoco.expediente', compact('eventoCecoco', 'detalle', 'filtros'));

        } catch (\Exception $e) {
            return redirect()
                ->route('cecoco.show', $eventoCecoco)
                ->with('error', 'Error al obtener el detalle del expediente: ' . $e->getMessage());
        }
    }

    public function mapaCalor()
    {
        $tipos = Cache::rememberForever('cecoco_tipos', function () {
            return EventoCecoco::distinct()->orderBy('tipo_servicio')->pluck('tipo_servicio');
        });

        return view('eventos-cecoco.mapa_calor_eventos', compact('tipos'));
    }

    public function mapaCalorDatos(Request $request)
    {
        $request->validate([
            'fecha_desde' => 'required|date',
            'fecha_hasta' => 'required|date',
        ]);

        // Aumentar el límite de tiempo a 5 minutos para permitir que las geocodificaciones 
        // masivas a Google Maps terminen con éxito en su primera pasada.
        set_time_limit(300);

        try {
            $geocoder = app(\App\Services\GeocodificacionService::class);

            $query = EventoCecoco::whereBetween('fecha_hora', [
                $request->fecha_desde,
                $request->fecha_hasta,
            ]);

            if ($request->filled('tipo_servicio')) {
                $query->where('tipo_servicio', $request->tipo_servicio);
            }

            // Calcular el total de eventos antes de modificar el query con el group by
            $totalEventos = $query->count();

            // Clonar para no arrastrar el count y aplicar agrupaciones
            $eventosQuery = clone $query;

            // Agrupar por dirección y contar ocurrencias
            $eventos = $eventosQuery->selectRaw('direccion, COUNT(*) as total')
                ->whereNotNull('direccion')
                ->where('direccion', '!=', '')
                ->groupBy('direccion')
                ->orderByDesc('total')
                ->limit(5000)
                ->get();

            $heatData = [];
            $sinGeocod = 0;
            $geocodificados = 0;

            foreach ($eventos as $evento) {
                $direccion = trim($evento->direccion);

                // Si la dirección no tiene numeración, intentar extraer del primer
                // evento con esa dirección que tenga descripción
                if (!$geocoder->tieneNumeracion($direccion)) {
                    $eventoConDesc = EventoCecoco::where('direccion', $evento->direccion)
                        ->whereNotNull('descripcion')
                        ->where('descripcion', '!=', '')
                        ->whereBetween('fecha_hora', [$request->fecha_desde, $request->fecha_hasta])
                        ->first();

                    if ($eventoConDesc) {
                        $dirExtraida = $geocoder->extraerDireccionDeDescripcion($eventoConDesc->descripcion);
                        if ($dirExtraida) {
                            $direccion = $dirExtraida;
                        }
                    }
                }

                $coords = $geocoder->geocodificar($direccion);

                if ($coords) {
                    $heatData[] = [
                        'lat' => $coords['lat'],
                        'lng' => $coords['lng'],
                        'peso' => $evento->total,
                        'direccion' => $evento->direccion,
                        'total' => $evento->total,
                    ];
                    $geocodificados++;
                } else {
                    $sinGeocod++;
                }
            }

            return response()->json([
                'heat_data' => $heatData,
                'total_eventos' => $totalEventos,
                'total_direcciones' => count($eventos),
                'geocodificados' => $geocodificados,
                'sin_geocodificar' => $sinGeocod,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al procesar datos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Busca grabaciones para el evento: primero en disco local, luego en CECOCO web.
     */
    public function grabaciones(EventoCecoco $eventoCecoco): JsonResponse
    {
        $this->authorize('ver-grabacion-evento');

        if (empty($eventoCecoco->telefono)) {
            return response()->json([
                'success'     => false,
                'message'     => 'El evento no tiene número de teléfono registrado. No es posible buscar grabaciones.',
                'grabaciones' => [],
            ]);
        }

        try {
            // 1. Buscar en disco local (rápido)
            $localService = new CecocoGrabacionesLocalService();
            $resultado    = $localService->buscarGrabaciones(
                $eventoCecoco->telefono,
                $eventoCecoco->fecha_hora
            );

            // Completar la URL de stream local para cada grabación
            foreach ($resultado['grabaciones'] as &$g) {
                $g['url'] = route('api.cecoco.grabacion.stream.local', [
                    'path' => base64_encode($g['path']),
                ]);
                unset($g['path']); // no exponer la ruta física al cliente
            }
            unset($g);

            // 2. Si no se encontró nada localmente, intentar vía CECOCO web
            if (empty($resultado['grabaciones']) && config('cecoco.url')) {
                $cecocoService = new CecocoGrabacionesService();
                $resultado     = $cecocoService->buscarGrabaciones(
                    $eventoCecoco->telefono,
                    $eventoCecoco->fecha_hora
                );
            }

            return response()->json([
                'success'     => true,
                'grabaciones' => $resultado['grabaciones'],
                'total'       => count($resultado['grabaciones']),
                'ventana'     => $resultado['ventana'],
                'fuente'      => $resultado['fuente'] ?? 'cecoco',
            ]);
        } catch (\Exception $e) {
            Log::error('grabaciones evento cecoco', [
                'evento_id' => $eventoCecoco->id,
                'error'     => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al buscar grabaciones: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Stream de grabación desde disco local.
     */
    public function streamGrabacionLocal(Request $request)
    {
        $this->authorize('ver-grabacion-evento');

        $pathEncoded = $request->input('path');
        if (!$pathEncoded) {
            return response()->json(['success' => false, 'message' => 'Path requerido.'], 400);
        }

        $filepath = base64_decode($pathEncoded);

        // Validar que el archivo esté dentro del directorio base permitido
        $servicio = new CecocoGrabacionesLocalService();
        if (!$servicio->validarPath($filepath)) {
            return response()->json(['success' => false, 'message' => 'Archivo no permitido.'], 403);
        }

        if (!file_exists($filepath)) {
            return response()->json(['success' => false, 'message' => 'Archivo no encontrado.'], 404);
        }

        $nombre = basename($filepath);
        $ext    = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));
        $mime   = match ($ext) {
            'wav'  => 'audio/wav',
            'mp3'  => 'audio/mpeg',
            'ogg'  => 'audio/ogg',
            'aac'  => 'audio/aac',
            default => 'application/octet-stream',
        };

        $disposition = $request->boolean('download') ? 'attachment' : 'inline';

        return response()->file($filepath, [
            'Content-Type'        => $mime,
            'Content-Disposition' => $disposition . '; filename="' . rawurlencode($nombre) . '"',
        ]);
    }

    /**
     * Proxy para reproducir o descargar un audio desde CECOCO (requiere auth).
     */
    public function streamGrabacion(Request $request)
    {
        $request->validate(['url' => 'required|string']);

        $url = $request->input('url');

        // Extraer nombre del archivo desde el query param "nombreFichero"
        parse_str(parse_url($url, PHP_URL_QUERY) ?? '', $qParams);
        $nombre = $qParams['nombreFichero'] ?? basename(parse_url($url, PHP_URL_PATH));
        if (empty($nombre)) $nombre = 'grabacion.wav';

        // Sanitizar: solo permitir URLs del servidor CECOCO
        $cecocoHost = parse_url(config('cecoco.url'), PHP_URL_HOST);
        $urlHost    = parse_url($url, PHP_URL_HOST);
        if ($urlHost !== $cecocoHost) {
            return response()->json(['success' => false, 'message' => 'URL no permitida.'], 403);
        }

        try {
            $servicio  = new CecocoGrabacionesService();
            $response  = $servicio->descargarAudio($url);

            $statusCode  = $response->getStatusCode();
            $contentType = $response->getHeaderLine('Content-Type');
            $bodySize    = $response->getBody()->getSize();

            Log::debug('streamGrabacion: respuesta CECOCO', [
                'url'          => $url,
                'status'       => $statusCode,
                'content_type' => $contentType,
                'body_size'    => $bodySize,
            ]);

            if ($statusCode !== 200) {
                return response()->json(['success' => false, 'message' => 'Archivo no encontrado en CECOCO.'], 404);
            }

            // Si CECOCO devuelve HTML en lugar de audio, el archivo no existe o la sesión expiró
            if (str_starts_with($contentType, 'text/html')) {
                return response()->json(['success' => false, 'message' => 'El archivo de audio no está disponible en el servidor CECOCO.'], 404);
            }

            $ext  = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));
            $mime = match ($ext) {
                'wav'  => 'audio/wav',
                'mp3'  => 'audio/mpeg',
                'ogg'  => 'audio/ogg',
                'aac'  => 'audio/aac',
                default => 'application/octet-stream',
            };

            $disposition = $request->boolean('download') ? 'attachment' : 'inline';

            $headers = [
                'Content-Type'        => $mime,
                'Content-Disposition' => $disposition . '; filename="' . rawurlencode($nombre) . '"',
                'Accept-Ranges'       => 'bytes',
            ];

            // Pasar Content-Length para que el navegador muestre la duración del audio
            $contentLength = $response->getHeaderLine('Content-Length');
            if ($contentLength !== '') {
                $headers['Content-Length'] = $contentLength;
            }

            return response($response->getBody()->getContents(), 200, $headers);
        } catch (\Exception $e) {
            Log::error('stream grabacion cecoco', ['url' => $url, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'No se pudo acceder al audio.'], 404);
        }
    }

    /**
     * Normaliza variantes de "D.D." / "dd" / "D.d" a "Dispositivo Dual".
     */
    private static function normalizarTipo(?string $tipo): string
    {
        if (!$tipo) return '(sin tipo)';
        // Coincide con: "D.D.", "dd", "D.d", "D.D", "d.d." etc.
        if (preg_match('/^[Dd]\.?\s*[Dd]\.?$/u', trim($tipo))) {
            return 'Dispositivo Dual';
        }
        return trim($tipo);
    }

    public function analitica(Request $request)
    {
        $tipos = Cache::rememberForever('cecoco_tipos', function () {
            return EventoCecoco::distinct()->orderBy('tipo_servicio')->pluck('tipo_servicio');
        });

        // Normalizar y deduplicar para el filtro
        $tipos = $tipos->filter()->map(fn($t) => self::normalizarTipo($t))->unique()->sort()->values();

        return view('eventos-cecoco.analitica', compact('tipos'));
    }

    public function analiticaDatos(Request $request): JsonResponse
    {
        $desde = $request->filled('desde')
            ? $request->desde . ' 00:00:00'
            : now()->subDays(6)->startOfDay()->format('Y-m-d H:i:s');

        $hasta = $request->filled('hasta')
            ? $request->hasta . ' 23:59:59'
            : now()->endOfDay()->format('Y-m-d H:i:s');

        $tipo = $request->input('tipo');

        $base = EventoCecoco::whereBetween('fecha_hora', [$desde, $hasta]);

        if ($tipo) {
            if ($tipo === 'Dispositivo Dual') {
                // Busca todas las variantes: D.D., dd, D.d, D.D, d.d. etc.
                $base->whereRaw("tipo_servicio REGEXP '^[Dd]\\.?[[:space:]]*[Dd]\\.?$'");
            } else {
                $base->where('tipo_servicio', 'like', '%' . $tipo . '%');
            }
        }

        // Total
        $total = (clone $base)->count();

        // Llamadas por día calendario (tendencia)
        $porFechaRaw = (clone $base)
            ->select(DB::raw('DATE(fecha_hora) as fecha'), DB::raw('COUNT(*) as total'))
            ->groupBy('fecha')
            ->orderBy('fecha')
            ->get();

        $porFecha = [];
        foreach ($porFechaRaw as $row) {
            $porFecha[$row->fecha] = (int) $row->total;
        }

        // Promedio diario
        $diasPeriodo   = max(1, \Carbon\Carbon::parse($desde)->diffInDays(\Carbon\Carbon::parse($hasta)) + 1);
        $promedioDiario = round($total / $diasPeriodo, 1);

        // Por hora del día (0-23)
        $porHoraRaw = (clone $base)
            ->select(DB::raw('HOUR(fecha_hora) as hora'), DB::raw('COUNT(*) as total'))
            ->groupBy('hora')
            ->orderBy('hora')
            ->get()
            ->keyBy('hora');

        $porHora = [];
        for ($h = 0; $h < 24; $h++) {
            $porHora[$h] = $porHoraRaw->has($h) ? (int) $porHoraRaw[$h]->total : 0;
        }

        // Por día de semana (MySQL: 1=Dom, 2=Lun, ..., 7=Sáb → reordenar a Lun-Dom)
        $porDiaRaw = (clone $base)
            ->select(DB::raw('DAYOFWEEK(fecha_hora) as dia'), DB::raw('COUNT(*) as total'))
            ->groupBy('dia')
            ->orderBy('dia')
            ->get()
            ->keyBy('dia');

        // MySQL DAYOFWEEK: 1=Dom, 2=Lun, 3=Mar, 4=Mié, 5=Jue, 6=Vie, 7=Sáb
        $diasOrden = [2, 3, 4, 5, 6, 7, 1]; // Lun → Dom
        $diasNombres = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
        $porDia = [];
        foreach ($diasOrden as $idx => $mysqlDia) {
            $porDia[$diasNombres[$idx]] = $porDiaRaw->has($mysqlDia) ? (int) $porDiaRaw[$mysqlDia]->total : 0;
        }

        // Top tipificaciones (con normalización de D.D. → Dispositivo Dual)
        $topTipos = (clone $base)
            ->select('tipo_servicio', DB::raw('COUNT(*) as total'))
            ->whereNotNull('tipo_servicio')
            ->groupBy('tipo_servicio')
            ->orderByDesc('total')
            ->get()
            ->groupBy(fn($r) => self::normalizarTipo($r->tipo_servicio))
            ->map(fn($group, $tipo) => ['tipo' => $tipo, 'total' => (int) $group->sum('total')])
            ->sortByDesc('total')
            ->values()
            ->take(15);

        // Top calles (primer segmento de la dirección)
        $topCalles = (clone $base)
            ->select(
                DB::raw("TRIM(SUBSTRING_INDEX(UPPER(direccion), ' AL ', 1)) as calle"),
                DB::raw('COUNT(*) as total')
            )
            ->whereNotNull('direccion')
            ->where('direccion', '!=', '')
            ->groupBy('calle')
            ->orderByDesc('total')
            ->limit(15)
            ->get()
            ->map(fn($r) => ['calle' => $r->calle, 'total' => (int) $r->total]);

        // Franja horaria pico
        $horaPico = array_search(max($porHora), $porHora);
        $horaPicoLabel = sprintf('%02d:00 - %02d:59', $horaPico, $horaPico);

        // Dia pico
        $diaPico = array_search(max(array_values($porDia)), array_values($porDia));
        $diaPicoNombre = $diasNombres[$diaPico] ?? '-';

        return response()->json([
            'total'           => $total,
            'desde'           => $desde,
            'hasta'           => $hasta,
            'por_hora'        => $porHora,
            'por_dia'         => $porDia,
            'top_tipos'       => $topTipos,
            'top_calles'      => $topCalles,
            'hora_pico'       => $horaPicoLabel,
            'dia_pico'        => $diaPicoNombre,
            'por_fecha'       => $porFecha,
            'promedio_diario' => $promedioDiario,
        ]);
    }
}

