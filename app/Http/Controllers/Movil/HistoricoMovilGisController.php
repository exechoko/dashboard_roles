<?php

namespace App\Http\Controllers\Movil;

use App\Http\Controllers\Controller;
use App\Models\HistoricoMovilProcesado;
use App\Services\CecocoGisService;
use App\Services\GeocodificacionService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Versión PWA del "Histórico Móvil GIS" de escritorio: consulta el GIS viewer
 * de CECOCO para extraer el recorrido de un móvil, permite ver consultas
 * previas y compartir el PDF/recorrido por WhatsApp.
 *
 * Comparte la tabla historico_movil_procesados y el prefijo "[GIS] " con
 * App\Http\Controllers\HistoricoMovilGisController (escritorio): una consulta
 * hecha desde el celular aparece también en "consultas previas" de escritorio
 * y viceversa.
 */
class HistoricoMovilGisController extends Controller
{
    private const ORIGEN_PREFIJO = '[GIS] ';

    public function __construct()
    {
        $this->middleware('permission:ver-historico-movil-gis-cecoco');
    }

    public function index(): View
    {
        return view('movil.historico-movil-gis.index', [
            'puedeCompartir' => auth()->user()->can('exportar-whatsapp-cecoco'),
        ]);
    }

    public function buscarRecurso(Request $request, CecocoGisService $gis)
    {
        $validated = $request->validate([
            'q'            => 'required|string|min:1|max:50',
            'fecha_inicio' => 'required|date',
            'fecha_fin'    => 'required|date|after_or_equal:fecha_inicio',
        ]);

        try {
            $items = $gis->buscarRecursos(
                $validated['q'],
                Carbon::parse($validated['fecha_inicio']),
                Carbon::parse($validated['fecha_fin'])
            );
        } catch (\Exception $e) {
            Log::error('Movil: error buscando recursos GIS', ['error' => $e->getMessage()]);
            return response()->json(['message' => $e->getMessage()], 502);
        }

        return response()->json(['items' => $items]);
    }

    public function buscarHistorial(Request $request)
    {
        $busqueda  = $request->input('q', '');
        $desde     = $request->input('desde', '');
        $hasta     = $request->input('hasta', '');
        $porPagina = 10;

        $query = HistoricoMovilProcesado::with('user')
            ->where('nombre_archivo', 'like', self::ORIGEN_PREFIJO . '%')
            ->orderByDesc('created_at');

        if ($busqueda) {
            $query->where(function ($q) use ($busqueda) {
                $q->where('recurso', 'like', "%{$busqueda}%")
                  ->orWhere('nombre_archivo', 'like', "%{$busqueda}%");
            });
        }

        if ($desde) {
            $query->whereDate('created_at', '>=', $desde);
        }
        if ($hasta) {
            $query->whereDate('created_at', '<=', $hasta);
        }

        $paginado = $query->paginate($porPagina);

        return response()->json([
            'items'         => $paginado->map(fn($h) => [
                'id'            => $h->id,
                'recurso'       => $h->recurso,
                'fecha_inicio'  => $h->fecha_inicio,
                'fecha_fin'     => $h->fecha_fin,
                'posiciones'    => $h->posiciones,
                'procesado_por' => $h->user?->name ?? 'N/D',
                'procesado_el'  => $h->created_at->format('d/m/Y H:i'),
            ]),
            'total'         => $paginado->total(),
            'pagina_actual' => $paginado->currentPage(),
            'ultima_pagina' => $paginado->lastPage(),
        ]);
    }

    public function consultar(Request $request, CecocoGisService $gis, GeocodificacionService $geo)
    {
        @set_time_limit(300);

        $validated = $request->validate([
            'recurso'          => 'required|string|max:50',
            'fecha_inicio'     => 'required|date',
            'fecha_fin'        => 'required|date|after_or_equal:fecha_inicio',
            'velocidad_maxima' => 'nullable|numeric|min:0',
            'umbral_naranja'   => 'nullable|integer|min:1',
            'umbral_rojo'      => 'nullable|integer|min:1',
        ]);

        $velocidadMaxima = $request->filled('velocidad_maxima') ? (float) $request->velocidad_maxima : 45;
        $umbralNaranja   = $request->filled('umbral_naranja')   ? (int) $request->umbral_naranja * 60 : 1800;
        $umbralRojo      = $request->filled('umbral_rojo')      ? (int) $request->umbral_rojo * 60    : 2700;

        try {
            $fechaInicio = Carbon::parse($validated['fecha_inicio']);
            $fechaFin    = Carbon::parse($validated['fecha_fin']);

            $resultadoGis = $gis->obtenerHistoricoMovil(
                trim($validated['recurso']),
                $fechaInicio,
                $fechaFin
            );
        } catch (\Exception $e) {
            Log::error('Movil: error consultando GIS histórico móvil', [
                'error'   => $e->getMessage(),
                'recurso' => $request->recurso,
            ]);
            return response()->json(['message' => $e->getMessage()], 502);
        }

        $posiciones = $resultadoGis['posiciones'] ?? [];
        $registros  = $this->construirRegistros($posiciones, $geo, $velocidadMaxima, $umbralNaranja, $umbralRojo);

        $metadata = [
            'recurso'      => $resultadoGis['recurso'] ?? trim($validated['recurso']),
            'fecha_inicio' => $fechaInicio->format('d/m/Y H:i:s'),
            'fecha_fin'    => $fechaFin->format('d/m/Y H:i:s'),
            'posiciones'   => count($registros),
        ];

        $historial = HistoricoMovilProcesado::create([
            'user_id'          => Auth::id(),
            'nombre_archivo'   => self::ORIGEN_PREFIJO . $metadata['recurso'] . ' ' . $fechaInicio->format('YmdHi') . '-' . $fechaFin->format('YmdHi'),
            'recurso'          => $metadata['recurso'],
            'fecha_inicio'     => $metadata['fecha_inicio'],
            'fecha_fin'        => $metadata['fecha_fin'],
            'posiciones'       => count($registros),
            'velocidad_maxima' => $velocidadMaxima,
            'umbral_naranja'   => $umbralNaranja / 60,
            'umbral_rojo'      => $umbralRojo / 60,
            'metadata'         => $metadata,
            'registros_json'   => json_encode($registros),
        ]);

        return response()->json([
            'historial_id' => $historial->id,
            'metadata'     => $metadata,
            'registros'    => $registros,
        ]);
    }

    public function cargarHistorial(HistoricoMovilProcesado $historial)
    {
        return response()->json([
            'historial_id' => $historial->id,
            'metadata'     => $historial->metadata,
            'registros'    => json_decode($historial->registros_json, true),
        ]);
    }

    public function eliminarHistorial(HistoricoMovilProcesado $historial)
    {
        $historial->delete();
        return response()->json(['ok' => true]);
    }

    /**
     * Genera el PDF del recorrido para compartir por WhatsApp (Web Share API
     * del lado del cliente, ver movil/historico-movil-gis/index.blade.php).
     */
    public function pdf(HistoricoMovilProcesado $historial)
    {
        $this->authorize('exportar-whatsapp-cecoco');

        // Recorridos largos (hay hasta ~2000 posiciones) hacen que el motor de
        // layout de DomPDF agote la memoria por defecto (probado: 2000 filas
        // revienta incluso con 512M). No hay forma de evitarlo salvo achicar
        // la tabla, así que se sube el límite puntualmente para esta acción.
        @ini_set('memory_limit', '768M');
        @set_time_limit(90);

        $registros = json_decode($historial->registros_json, true) ?? [];

        $pdf = Pdf::loadView('movil.historico-movil-gis.pdf', [
            'historial' => $historial,
            'registros' => $registros,
        ])->setPaper('a4', 'landscape');

        $nombreArchivo = 'HistoricoMovil_' . preg_replace('/[^A-Za-z0-9_-]+/', '_', $historial->recurso) . '.pdf';

        return $pdf->stream($nombreArchivo);
    }

    /**
     * Genera un HTML autocontenido (Leaflet + datos embebidos) con el mapa del
     * recorrido, para compartir como archivo por WhatsApp y poder abrirlo
     * offline sin depender del sistema.
     */
    public function recorrido(HistoricoMovilProcesado $historial)
    {
        $this->authorize('exportar-whatsapp-cecoco');

        $registros = json_decode($historial->registros_json, true) ?? [];

        $html = view('movil.historico-movil-gis.recorrido-standalone', [
            'historial' => $historial,
            'registros' => $registros,
        ])->render();

        $nombreArchivo = 'Recorrido_' . preg_replace('/[^A-Za-z0-9_-]+/', '_', $historial->recurso) . '.html';

        return response($html, 200, [
            'Content-Type'        => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'inline; filename="' . $nombreArchivo . '"',
        ]);
    }

    /**
     * Construye los registros normalizados (con geocodificación inversa, estado
     * detenido/movimiento, tiempo detenido acumulado y exceso de velocidad) a
     * partir de las posiciones crudas del GIS. Réplica de
     * App\Http\Controllers\HistoricoMovilGisController::construirRegistros(),
     * duplicada a propósito para no modificar el controlador de escritorio.
     *
     * @param array<int,array{fecha:\Carbon\Carbon,lat:float,lng:float,velocidad:float,direccion:?string}> $posiciones
     */
    private function construirRegistros(
        array $posiciones,
        GeocodificacionService $geo,
        float $velocidadMaxima,
        int $umbralNaranja,
        int $umbralRojo
    ): array {
        $registros             = [];
        $estaDetenido          = false;
        $tiempoInicio          = null;
        $indiceInicioDetencion = null;

        $paresCoords = [];
        foreach ($posiciones as $pos) {
            $lat = isset($pos['lat']) ? (float) $pos['lat'] : null;
            $lng = isset($pos['lng']) ? (float) $pos['lng'] : null;
            if ($lat !== null && $lng !== null && empty($pos['direccion'])) {
                $paresCoords[] = [$lat, $lng];
            }
        }
        $direcciones = !empty($paresCoords) ? $geo->reverseGeocodeBatch($paresCoords) : [];

        foreach ($posiciones as $i => $pos) {
            $fecha     = $pos['fecha'] instanceof Carbon ? $pos['fecha'] : Carbon::parse($pos['fecha']);
            $lat       = isset($pos['lat']) ? (float) $pos['lat'] : null;
            $lng       = isset($pos['lng']) ? (float) $pos['lng'] : null;
            $velocidad = (float) ($pos['velocidad'] ?? 0);

            $direccion = $pos['direccion'] ?? null;
            if (!$direccion && $lat !== null && $lng !== null) {
                $clave     = sprintf('%.5f,%.5f', $lat, $lng);
                $direccion = $direcciones[$clave] ?? '';
            }

            $excesoVelocidad = false;

            if ($velocidad == 0) {
                if (!$estaDetenido) {
                    $estaDetenido          = true;
                    $tiempoInicio          = $fecha;
                    $indiceInicioDetencion = count($registros);
                }
                $estado      = 'Detenido';
                $colorEstado = 'detenido';
            } else {
                if ($estaDetenido) {
                    $estaDetenido   = false;
                    $segundos       = $fecha->diffInSeconds($tiempoInicio);
                    $ultimoDetenido = count($registros) - 1;
                    if (isset($registros[$indiceInicioDetencion])) {
                        $registros[$ultimoDetenido]['tiempo_detenido']   = $this->formatearTiempo($segundos);
                        $registros[$ultimoDetenido]['color_tiempo']      = $this->colorPorTiempo($segundos, $umbralNaranja, $umbralRojo);
                        $registros[$ultimoDetenido]['segundos_detenido'] = $segundos;
                    }
                }
                $estado      = 'En movimiento';
                $colorEstado = 'movimiento';
                if ($velocidadMaxima > 0 && $velocidad > $velocidadMaxima) {
                    $excesoVelocidad = true;
                }
            }

            $enlace = ($lat !== null && $lng !== null)
                ? "https://www.google.com/maps?q={$lat},{$lng}"
                : null;

            $registros[] = [
                'id'                => $i + 1,
                'fecha'             => $fecha->format('d/m/Y H:i:s'),
                'velocidad'         => $velocidad,
                'direccion'         => $direccion,
                'lat'               => $lat,
                'lng'               => $lng,
                'enlace'            => $enlace,
                'estado'            => $estado,
                'color_estado'      => $colorEstado,
                'tiempo_detenido'   => null,
                'color_tiempo'      => null,
                'segundos_detenido' => null,
                'exceso_velocidad'  => $excesoVelocidad,
            ];
        }

        if ($estaDetenido && !empty($registros) && !empty($posiciones)) {
            $ultimaPos   = end($posiciones);
            $ultimaFecha = $ultimaPos['fecha'] instanceof Carbon ? $ultimaPos['fecha'] : Carbon::parse($ultimaPos['fecha']);
            $segundos    = $ultimaFecha->diffInSeconds($tiempoInicio);
            $ultimo      = count($registros) - 1;
            $registros[$ultimo]['tiempo_detenido']   = $this->formatearTiempo($segundos);
            $registros[$ultimo]['color_tiempo']      = $this->colorPorTiempo($segundos, $umbralNaranja, $umbralRojo);
            $registros[$ultimo]['segundos_detenido'] = $segundos;
        }

        return $registros;
    }

    private function formatearTiempo(int $segundos): string
    {
        $h = intdiv($segundos, 3600);
        $m = intdiv($segundos % 3600, 60);
        $s = $segundos % 60;
        return "{$h} hs {$m} min {$s} seg";
    }

    private function colorPorTiempo(int $segundos, int $umbralNaranja = 1800, int $umbralRojo = 2700): string
    {
        if ($segundos >= $umbralRojo)    return 'red';
        if ($segundos >= $umbralNaranja) return 'orange';
        return 'yellow';
    }
}
