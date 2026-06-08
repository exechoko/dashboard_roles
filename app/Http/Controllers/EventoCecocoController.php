<?php

namespace App\Http\Controllers;

use App\Http\Requests\AnaliticaEventoCecocoRequest;
use App\Models\EventoCecoco;
use App\Models\Importacion;
use App\Services\EventoCecocoParser;
use App\Services\CecocoExpedienteService;
use App\Services\CecocoGrabacionesService;
use App\Services\CecocoGrabacionesLocalService;
use App\Services\GrabadorTetraService;
use App\Services\CecocoModulacionesLocalService;
use App\Services\ResumenEventoIaService;
use App\Jobs\DescargarEventosCecoco;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EventoCecocoController extends Controller
{
    private EventoCecocoParser $parser;
    private CecocoExpedienteService $expedienteService;

    public function __construct(
        EventoCecocoParser $parser,
        CecocoExpedienteService $expedienteService,
        private ResumenEventoIaService $resumenIaService
    ) {
        $this->parser = $parser;
        $this->expedienteService = $expedienteService;
    }

    public function index(Request $request)
    {
        $eventos = null;
        $totalResultados = null;
        $tieneFiltros = $request->hasAny(['anio', 'mes', 'operador', 'tipo', 'tipos', 'desde_datetime', 'hasta_datetime', 'desde', 'hasta', 'buscar']);

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
            ])->withExists(['detalle as tiene_detalle' => function ($q) {
                $q->whereNotNull('detalle_json');
            }]);

            if ($request->filled('anio')) {
                $query->delAnio((int) $request->anio);
            }

            if ($request->filled('mes')) {
                $query->delMes((int) $request->mes);
            }

            if ($request->filled('operador')) {
                $query->porOperador($request->operador);
            }

            $tiposFiltro = $this->tiposDesdeRequest($request);
            if ($tiposFiltro !== []) {
                $this->aplicarFiltroTipos($query, $tiposFiltro);
            } elseif ($request->filled('tipo')) {
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
                'tipos' => $request->input('tipos'),
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

            $eventos->getCollection()->transform(function ($e) {
                if (preg_match('/^(d\.?d\.?|dd)(?=\s|$)/i', trim($e->direccion))) {
                    $e->direccion = preg_replace('/^(d\.?d\.?|dd)(?=\s|$)/i', 'Dispositivo Dual', trim($e->direccion));
                }
                return $e;
            });
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
        $eventoCecoco->load('importacion', 'detalle');
        $filtros = $request->only([
            'anio',
            'mes',
            'operador',
            'tipo',
            'tipos',
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
        $importaciones = Importacion::select([
            'id',
            'nombre_archivo',
            'anio',
            'registros_importados',
            'registros_duplicados',
            'registros_omitidos',
            'tiempo_procesamiento',
            'estado',
            'created_at',
        ])->orderByDesc('created_at')->simplePaginate(20);

        $totalArchivosImportados = Cache::remember('cecoco_total_archivos_importados', 300, function () {
            return Importacion::where('estado', 'completado')->count();
        });

        $totalRegistrosEnBd = Cache::remember('cecoco_total_bd_importar', 300, function () {
            return (int) Importacion::where('estado', 'completado')->sum('registros_importados');
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

    public function importarHoy()
    {
        $importacion = Importacion::create([
            'nombre_archivo' => 'reporte_' . now()->format('Y_m_d') . '.xls',
            'estado' => 'pendiente',
        ]);

        DescargarEventosCecoco::dispatch(now()->toDateString(), $importacion->id, true);

        return redirect()->route('cecoco.importar')
            ->with('success', 'Importación de eventos de hoy agregada a la cola. La descarga y el procesamiento se ejecutan en segundo plano.');
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

        $tiposFiltro = $this->tiposDesdeRequest($request);
        if ($tiposFiltro !== []) {
            $this->aplicarFiltroTipos($query, $tiposFiltro);
        } elseif ($request->filled('tipo')) {
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

        $tiposFiltro = $this->tiposDesdeRequest($request);
        if ($tiposFiltro !== []) {
            $this->aplicarFiltroTipos($query, $tiposFiltro);
        } elseif ($request->filled('tipo')) {
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
                'tipos',
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

    /**
     * Genera (o devuelve cacheado) un resumen estructurado del evento usando IA local.
     */
    public function resumenIa(Request $request, EventoCecoco $eventoCecoco): JsonResponse
    {
        $this->authorize('ver-expediente-cecoco');

        if (!config('ia.enabled')) {
            return response()->json([
                'success' => false,
                'message' => 'La función de resumen con IA está desactivada.',
            ], 503);
        }

        // La inferencia en CPU puede tardar; evitamos que PHP corte la request.
        set_time_limit((int) config('ia.timeout', 180) + 30);

        $refrescar = $request->boolean('refrescar');

        try {
            $cache = \App\Models\DetalleExpedienteCecoco::where('evento_cecoco_id', $eventoCecoco->id)->first();

            // Devolver el resumen ya persistido si existe y no se pide refrescar.
            if (!$refrescar && $cache && !empty($cache->resumen_ia)) {
                return response()->json([
                    'success' => true,
                    'resumen' => $cache->resumen_ia,
                    'generado_en' => optional($cache->resumen_ia_generado_en)->format('d/m/Y H:i'),
                    'cacheado' => true,
                ]);
            }

            // Asegurar que tenemos el detalle del expediente para alimentar a la IA.
            $detalle = $cache?->detalle_json;
            if (!$detalle) {
                $detalle = $this->expedienteService->obtenerDetalleExpediente($eventoCecoco->nro_expediente);
                $cache = \App\Models\DetalleExpedienteCecoco::updateOrCreate(
                    ['evento_cecoco_id' => $eventoCecoco->id],
                    [
                        'nro_expediente' => $eventoCecoco->nro_expediente,
                        'detalle_json' => $detalle,
                        'fecha_consulta' => now(),
                    ]
                );
            }

            $resumen = $this->resumenIaService->resumir($detalle);

            $cache->update([
                'resumen_ia' => $resumen,
                'resumen_ia_generado_en' => now(),
            ]);

            return response()->json([
                'success' => true,
                'resumen' => $resumen,
                'generado_en' => now()->format('d/m/Y H:i'),
                'cacheado' => false,
            ]);
        } catch (\Exception $e) {
            Log::error('resumen IA evento cecoco', [
                'evento_id' => $eventoCecoco->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
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
            $eventos = $eventosQuery->selectRaw('direccion, COUNT(*) as total, MIN(nro_expediente) as nro_expediente_muestra, MIN(descripcion) as descripcion_muestra')
                ->whereNotNull('direccion')
                ->where('direccion', '!=', '')
                ->groupBy('direccion')
                ->orderByDesc('total')
                ->limit(5000)
                ->get();

            $heatData        = [];
            $sinGeocodDatos  = [];
            $sinGeocod       = 0;
            $geocodificados  = 0;

            foreach ($eventos as $evento) {
                $direccion = trim($evento->direccion);

                // geocodificar() consulta el cache ANTES de validar formato, por lo que
                // direcciones inválidas corregidas manualmente (vía mapa) son resueltas aquí.
                $coords = $geocoder->geocodificar($direccion, $evento->nro_expediente_muestra ?? null);

                if ($coords) {
                    $heatData[] = [
                        'lat'       => $coords['lat'],
                        'lng'       => $coords['lng'],
                        'peso'      => $evento->total,
                        'direccion' => $evento->direccion,
                        'total'     => $evento->total,
                    ];
                    $geocodificados++;
                } else {
                    $sinGeocod++;
                    $motivo = $geocoder->esDireccionValida($direccion) ? 'no_encontrada' : 'invalida';
                    $sinGeocodDatos[] = [
                        'direccion'      => $evento->direccion,
                        'total'          => $evento->total,
                        'motivo'         => $motivo,
                        'nro_expediente' => $evento->nro_expediente_muestra,
                        'descripcion'    => $evento->descripcion_muestra,
                    ];
                }
            }

            return response()->json([
                'heat_data'             => $heatData,
                'total_eventos'         => $totalEventos,
                'total_direcciones'     => count($eventos),
                'geocodificados'        => $geocodificados,
                'sin_geocodificar'      => $sinGeocod,
                'sin_geocodificar_datos'=> $sinGeocodDatos,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al procesar datos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Geocodifica manualmente una dirección inválida o no encontrada.
     * Guarda el resultado en caché usando la dirección original como clave.
     */
    public function geocodificarManual(Request $request): JsonResponse
    {
        $request->validate([
            'direccion_original' => 'required|string|max:500',
            'direccion_corregida' => 'required|string|max:500',
            'nro_expediente'     => 'nullable|string|max:100',
        ]);

        $geocoder  = app(\App\Services\GeocodificacionService::class);
        $original  = trim($request->direccion_original);
        $corregida = trim($request->direccion_corregida);

        if (!$geocoder->esDireccionValida($corregida)) {
            return response()->json(['error' => 'La dirección ingresada no parece válida (debe tener número o ser una intersección).'], 422);
        }

        // Si la dirección corregida ya fue consultada antes y quedó guardada sin coordenadas
        // (Google no la encontró en ese momento), eliminamos ese registro para forzar un
        // nuevo intento con Google en lugar de devolver null desde caché.
        \App\Models\GeocodificacionDirecta::where('direccion_original', $corregida)
            ->whereNull('latitud')
            ->delete();

        $coords = $geocoder->geocodificar($corregida, $request->nro_expediente ?: null);

        // Si Google no encontró la dirección, intentar con Nominatim (OpenStreetMap) como respaldo.
        $fuente = 'google';
        if (!$coords) {
            $coords = $geocoder->geocodificarNominatim($corregida);
            $fuente = 'nominatim';
        }

        if (!$coords) {
            return response()->json(['error' => 'No se pudo ubicar esa dirección en Paraná con Google ni con OpenStreetMap. Verificá que sea correcta o usá el botón del mapa para fijar la ubicación manualmente.'], 422);
        }

        \App\Models\GeocodificacionDirecta::updateOrCreate(
            ['direccion_original' => $original],
            [
                'direccion_normalizada' => $corregida,
                'latitud'        => $coords['lat'],
                'longitud'       => $coords['lng'],
                'fuente'         => $fuente,
                'nro_expediente' => $request->nro_expediente ?: null,
            ]
        );

        return response()->json(['lat' => $coords['lat'], 'lng' => $coords['lng']]);
    }

    /**
     * Guarda coordenadas seleccionadas manualmente en el mapa para una dirección sin ubicar.
     */
    public function geocodificarCoordenadas(Request $request): JsonResponse
    {
        $request->validate([
            'direccion_original' => 'required|string|max:500',
            'lat'                => 'required|numeric|between:-90,90',
            'lng'                => 'required|numeric|between:-180,180',
            'nro_expediente'     => 'nullable|string|max:100',
        ]);

        \App\Models\GeocodificacionDirecta::updateOrCreate(
            ['direccion_original' => trim($request->direccion_original)],
            [
                'direccion_normalizada' => trim($request->direccion_original),
                'latitud'        => $request->lat,
                'longitud'       => $request->lng,
                'fuente'         => 'manual',
                'nro_expediente' => $request->nro_expediente ?: null,
            ]
        );

        return response()->json(['lat' => $request->lat, 'lng' => $request->lng]);
    }

    /**
     * Busca grabaciones para el evento: primero en disco local, luego en CECOCO web.
     */
    public function grabaciones(EventoCecoco $eventoCecoco): JsonResponse
    {
        $this->authorize('ver-grabacion-evento');

        if (empty($eventoCecoco->telefono)) {
            return response()->json([
                'success' => false,
                'message' => 'El evento no tiene número de teléfono registrado. No es posible buscar grabaciones.',
                'grabaciones' => [],
            ]);
        }

        try {
            // 1. Buscar en disco local (rápido)
            $localService = new CecocoGrabacionesLocalService();
            $resultado = $localService->buscarGrabaciones(
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
                $resultado = $cecocoService->buscarGrabaciones(
                    $eventoCecoco->telefono,
                    $eventoCecoco->fecha_hora
                );
            }

            return response()->json([
                'success' => true,
                'grabaciones' => $resultado['grabaciones'],
                'total' => count($resultado['grabaciones']),
                'ventana' => $resultado['ventana'],
                'fuente' => $resultado['fuente'] ?? 'cecoco',
            ]);
        } catch (\Exception $e) {
            Log::error('grabaciones evento cecoco', [
                'evento_id' => $eventoCecoco->id,
                'error' => $e->getMessage(),
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
        $ext = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'wav' => 'audio/wav',
            'mp3' => 'audio/mpeg',
            'ogg' => 'audio/ogg',
            'aac' => 'audio/aac',
            default => 'application/octet-stream',
        };

        $disposition = $request->boolean('download') ? 'attachment' : 'inline';

        return response()->file($filepath, [
            'Content-Type' => $mime,
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
        if (empty($nombre))
            $nombre = 'grabacion.wav';

        // Sanitizar: solo permitir URLs del servidor CECOCO
        $cecocoHost = parse_url(config('cecoco.url'), PHP_URL_HOST);
        $urlHost = parse_url($url, PHP_URL_HOST);
        if ($urlHost !== $cecocoHost) {
            return response()->json(['success' => false, 'message' => 'URL no permitida.'], 403);
        }

        try {
            $servicio = new CecocoGrabacionesService();
            $response = $servicio->descargarAudio($url);

            $statusCode = $response->getStatusCode();
            $contentType = $response->getHeaderLine('Content-Type');
            $bodySize = $response->getBody()->getSize();

            Log::debug('streamGrabacion: respuesta CECOCO', [
                'url' => $url,
                'status' => $statusCode,
                'content_type' => $contentType,
                'body_size' => $bodySize,
            ]);

            if ($statusCode !== 200) {
                return response()->json(['success' => false, 'message' => 'Archivo no encontrado en CECOCO.'], 404);
            }

            // Si CECOCO devuelve HTML en lugar de audio, el archivo no existe o la sesión expiró
            if (str_starts_with($contentType, 'text/html')) {
                return response()->json(['success' => false, 'message' => 'El archivo de audio no está disponible en el servidor CECOCO.'], 404);
            }

            $ext = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));
            $mime = match ($ext) {
                'wav' => 'audio/wav',
                'mp3' => 'audio/mpeg',
                'ogg' => 'audio/ogg',
                'aac' => 'audio/aac',
                default => 'application/octet-stream',
            };

            $disposition = $request->boolean('download') ? 'attachment' : 'inline';

            $headers = [
                'Content-Type' => $mime,
                'Content-Disposition' => $disposition . '; filename="' . rawurlencode($nombre) . '"',
                'Accept-Ranges' => 'bytes',
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
     * Busca modulaciones de radio TETRA del grabador para el evento, dentro de la
     * ventana que arranca N minutos antes de la fecha del evento y termina en su cierre.
     */
    public function modulaciones(EventoCecoco $eventoCecoco): JsonResponse
    {
        $this->authorize('escuchar-modulaciones-cecoco');

        if (empty($eventoCecoco->fecha_hora)) {
            return response()->json([
                'success'      => false,
                'message'      => 'El evento no tiene fecha/hora registrada. No es posible buscar modulaciones.',
                'modulaciones' => [],
            ]);
        }

        $minutosAntes = (int) config('grabador.minutos_antes', 15);
        $desde = $eventoCecoco->fecha_hora->copy()->subMinutes($minutosAntes);
        $hasta = $eventoCecoco->fecha_cierre
            ? $eventoCecoco->fecha_cierre->copy()
            : $eventoCecoco->fecha_hora->copy()->addMinutes((int) config('grabador.minutos_despues_sin_cierre', 60));

        try {
            // 1. Buscar en disco local (rápido, no consulta el grabador).
            $localService = new CecocoModulacionesLocalService();
            $resultado    = $localService->buscarModulaciones($desde, $hasta);

            foreach ($resultado['modulaciones'] as &$m) {
                $m['url'] = route('api.cecoco.modulacion.stream', ['path' => base64_encode($m['path'])]);
                unset($m['path']); // no exponer la ruta física al cliente
            }
            unset($m);

            // 2. Si no hubo resultados locales, consultar la web del grabador como respaldo.
            if (empty($resultado['modulaciones']) && config('grabador.url')) {
                $grabador  = new GrabadorTetraService();
                $resultado = $grabador->buscarModulaciones($desde, $hasta);

                foreach ($resultado['modulaciones'] as &$m) {
                    $m['url'] = route('api.cecoco.modulacion.stream', ['itemid' => $m['itemid']]);
                }
                unset($m);
            }

            return response()->json([
                'success'      => true,
                'modulaciones' => $resultado['modulaciones'],
                'total'        => count($resultado['modulaciones']),
                'ventana'      => $resultado['ventana'],
                'fuente'       => $resultado['fuente'] ?? 'grabador',
            ]);
        } catch (\Exception $e) {
            Log::error('modulaciones evento cecoco', [
                'evento_id' => $eventoCecoco->id,
                'error'     => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al buscar modulaciones: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Proxy para reproducir/descargar una modulación: desde disco local (path) o
     * desde el grabador web (itemid).
     */
    public function streamModulacion(Request $request)
    {
        $this->authorize('escuchar-modulaciones-cecoco');

        // Modulación en disco local.
        if ($request->filled('path')) {
            return $this->streamModulacionLocal($request);
        }

        $itemid = (string) $request->input('itemid', '');
        if (!preg_match('/^\d+_\d+$/', $itemid)) {
            return response()->json(['success' => false, 'message' => 'Identificador de modulación inválido.'], 400);
        }

        try {
            $servicio = new GrabadorTetraService();
            $response = $servicio->descargarAudio($itemid);

            $statusCode  = $response->getStatusCode();
            $contentType = $response->getHeaderLine('Content-Type');

            if ($statusCode !== 200 || !str_contains($contentType, 'audio')) {
                Log::warning('streamModulacion: respuesta no es audio', [
                    'itemid'       => $itemid,
                    'status'       => $statusCode,
                    'content_type' => $contentType,
                ]);

                return response()->json(['success' => false, 'message' => 'No se pudo obtener el audio de la modulación.'], 404);
            }

            $disposition = $request->boolean('download') ? 'attachment' : 'inline';
            $nombre      = 'modulacion_' . $itemid . '.wav';

            $headers = [
                'Content-Type'        => 'audio/wav',
                'Content-Disposition' => $disposition . '; filename="' . rawurlencode($nombre) . '"',
                'Accept-Ranges'       => 'bytes',
            ];

            $contentLength = $response->getHeaderLine('Content-Length');
            if ($contentLength !== '') {
                $headers['Content-Length'] = $contentLength;
            }

            return response($response->getBody()->getContents(), 200, $headers);
        } catch (\Exception $e) {
            Log::error('stream modulacion grabador', ['itemid' => $itemid, 'error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'No se pudo acceder al audio de la modulación.'], 404);
        }
    }

    /**
     * Sirve un archivo de modulación desde disco local (validando el path).
     */
    private function streamModulacionLocal(Request $request)
    {
        $filepath = base64_decode((string) $request->input('path'), true);
        if ($filepath === false) {
            return response()->json(['success' => false, 'message' => 'Path inválido.'], 400);
        }

        $servicio = new CecocoModulacionesLocalService();
        if (!$servicio->validarPath($filepath)) {
            return response()->json(['success' => false, 'message' => 'Archivo no permitido.'], 403);
        }

        if (!file_exists($filepath)) {
            return response()->json(['success' => false, 'message' => 'Archivo no encontrado.'], 404);
        }

        $nombre = basename($filepath);
        $ext    = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));
        $mime   = match ($ext) {
            'wav' => 'audio/wav',
            'mp3' => 'audio/mpeg',
            'ogg' => 'audio/ogg',
            'aac' => 'audio/aac',
            default => 'application/octet-stream',
        };

        $disposition = $request->boolean('download') ? 'attachment' : 'inline';

        return response()->file($filepath, [
            'Content-Type'        => $mime,
            'Content-Disposition' => $disposition . '; filename="' . rawurlencode($nombre) . '"',
        ]);
    }

    /**
     * Normaliza variantes de "D.D." / "dd" / "D.d" a "Dispositivo Dual".
     */
    private static function normalizarTipo(?string $tipo): string
    {
        if (!$tipo)
            return '(sin tipo)';
        // Coincide con: "D.D.", "dd", "D.d", "D.D", "d.d." etc.
        if (preg_match('/^[Dd]\.?\s*[Dd]\.?$/u', trim($tipo))) {
            return 'Dispositivo Dual';
        }
        return trim($tipo);
    }

    /**
     * @return array<int, string>
     */
    private function tiposDesdeRequest(Request $request): array
    {
        $tipos = $request->input('tipos', []);

        if (!is_array($tipos)) {
            $tipos = [$tipos];
        }

        return collect($tipos)
            ->filter(fn($tipo) => is_string($tipo) && trim($tipo) !== '')
            ->map(fn($tipo) => trim($tipo))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param array<int, string> $tipos
     */
    private function aplicarFiltroTipos(Builder $query, array $tipos): void
    {
        if ($tipos === []) {
            return;
        }

        $query->where(function (Builder $query) use ($tipos): void {
            foreach ($tipos as $tipo) {
                if ($tipo === 'Dispositivo Dual') {
                    $query->orWhereRaw("tipo_servicio REGEXP '^[Dd]\\.?[[:space:]]*[Dd]\\.?$'");
                } else {
                    $query->orWhere('tipo_servicio', 'like', '%' . $tipo . '%');
                }
            }
        });
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

    public function analiticaDatos(AnaliticaEventoCecocoRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $desde = $request->filled('desde')
            ? $validated['desde'] . ' 00:00:00'
            : now()->subDays(6)->startOfDay()->format('Y-m-d H:i:s');

        $hasta = $request->filled('hasta')
            ? $validated['hasta'] . ' 23:59:59'
            : now()->endOfDay()->format('Y-m-d H:i:s');

        $tiposFiltro = collect($validated['tipos'] ?? [])
            ->filter(fn($tipo) => is_string($tipo) && trim($tipo) !== '')
            ->map(fn($tipo) => trim($tipo))
            ->unique()
            ->values()
            ->all();

        if ($tiposFiltro === [] && !empty($validated['tipo'])) {
            $tiposFiltro = [$validated['tipo']];
        }

        $base = EventoCecoco::whereBetween('fecha_hora', [$desde, $hasta]);

        if ($tiposFiltro !== []) {
            $this->aplicarFiltroTipos($base, $tiposFiltro);
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
        $diasPeriodo = max(1, \Carbon\Carbon::parse($desde)->diffInDays(\Carbon\Carbon::parse($hasta)) + 1);
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
                DB::raw("
                    CASE 
                        WHEN UPPER(TRIM(SUBSTRING_INDEX(direccion, ' AL ', 1))) REGEXP '^(D[.]?D[.]?|DD)([[:space:]]|$)' 
                        THEN 'DISPOSITIVO DUAL'
                        ELSE UPPER(TRIM(SUBSTRING_INDEX(direccion, ' AL ', 1)))
                    END as calle
                "),
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

        // ── Comparativa Hechos de Relevancia (Período dinámico) ──
        $compararCon = $validated['comparar_con'] ?? 'mes'; // 'semana', 'mes', 'anio'

        $desdeAnteriorObj = \Carbon\Carbon::parse($desde);
        $hastaAnteriorObj = \Carbon\Carbon::parse($hasta);

        if ($compararCon === 'semana') {
            $desdeAnteriorObj->subWeek();
            $hastaAnteriorObj->subWeek();
        } elseif ($compararCon === 'anio') {
            $desdeAnteriorObj->subYear();
            $hastaAnteriorObj->subYear();
        } else {
            $desdeAnteriorObj->subMonth();
            $hastaAnteriorObj->subMonth();
        }

        $desdeAnterior = $desdeAnteriorObj->format('Y-m-d H:i:s');
        $hastaAnterior = $hastaAnteriorObj->format('Y-m-d H:i:s');
        $baseAnterior = EventoCecoco::whereBetween('fecha_hora', [$desdeAnterior, $hastaAnterior]);

        if ($tiposFiltro !== []) {
            $this->aplicarFiltroTipos($baseAnterior, $tiposFiltro);
        }

        $condicionesRel = [
            'Accidentes' => "LOWER(tipo_servicio) REGEXP 'accidente.*(lesion|herido|lesionad)'",
            'Robos' => "LOWER(tipo_servicio) LIKE '%robo%'",
            'Hurtos' => "LOWER(tipo_servicio) LIKE '%hurto%'",
            'Abuso Armas' => "LOWER(tipo_servicio) REGEXP 'abuso.*(arma|fuego)|arma de fuego'",
            'Homicidios' => "LOWER(tipo_servicio) LIKE '%homicidio%'"
        ];

        $comparativaActual = [];
        $comparativaAnterior = [];

        foreach ($condicionesRel as $label => $condition) {
            $comparativaActual[] = (clone $base)->whereRaw($condition)->count();
            $comparativaAnterior[] = (clone $baseAnterior)->whereRaw($condition)->count();
        }

        $eventos = (clone $base)
            ->select(['id', 'nro_expediente', 'fecha_hora', 'descripcion', 'tipo_servicio'])
            ->orderByDesc('fecha_hora')
            ->limit(100)
            ->get()
            ->map(function (EventoCecoco $evento): array {
                return [
                    'id' => $evento->id,
                    'nro_expediente' => $evento->nro_expediente ?: '-',
                    'fecha_hora' => $evento->fecha_hora ? $evento->fecha_hora->format('d/m/Y H:i') : '-',
                    'descripcion' => $evento->descripcion ?: '-',
                    'tipo_servicio' => $evento->tipo_servicio ?: '-',
                ];
            });

        return response()->json([
            'total' => $total,
            'desde' => $desde,
            'hasta' => $hasta,
            'por_hora' => $porHora,
            'por_dia' => $porDia,
            'top_tipos' => $topTipos,
            'top_calles' => $topCalles,
            'hora_pico' => $horaPicoLabel,
            'dia_pico' => $diaPicoNombre,
            'por_fecha' => $porFecha,
            'promedio_diario' => $promedioDiario,
            'comparativa_actual' => $comparativaActual,
            'comparativa_anterior' => $comparativaAnterior,
            'eventos' => $eventos,
            'eventos_limit' => 100,
        ]);
    }
}

