<?php

namespace App\Http\Controllers;

use App\Models\EventoCecoco;
use App\Models\Importacion;
use App\Services\EventoCecocoParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class EventoCecocoController extends Controller
{
    private EventoCecocoParser $parser;

    public function __construct(EventoCecocoParser $parser)
    {
        $this->parser = $parser;
    }

    public function index(Request $request)
    {
        $eventos = null;
        $tieneFiltros = $request->hasAny(['anio', 'mes', 'operador', 'tipo', 'desde', 'hasta', 'buscar']);

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
                $query->delAnio((int)$request->anio);
            }

            if ($request->filled('mes')) {
                $query->delMes((int)$request->mes);
            }

            if ($request->filled('operador')) {
                $query->porOperador($request->operador);
            }

            if ($request->filled('tipo')) {
                $query->porTipo($request->tipo);
            }

            if ($request->filled('desde') && $request->filled('hasta')) {
                $desdeCompleto = $request->desde . ' ' . ($request->filled('hora_desde') ? $request->hora_desde : '00:00:00');
                $hastaCompleto = $request->hasta . ' ' . ($request->filled('hora_hasta') ? $request->hora_hasta : '23:59:59');
                $query->whereBetween('fecha_hora', [$desdeCompleto, $hastaCompleto]);
            }

            if ($request->filled('buscar')) {
                $query->buscar($request->buscar);
            }

            $eventos = $query->orderBy('fecha_hora', 'desc')->paginate(50)->withQueryString();
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

        $totalEnBd = Cache::rememberForever('cecoco_total_bd', function () {
            return EventoCecoco::count();
        });

        $totalImportaciones = Cache::rememberForever('cecoco_total_importaciones', function () {
            return Importacion::count();
        });

        return view('eventos-cecoco.index', compact(
            'eventos',
            'anios',
            'tipos',
            'operadores',
            'meses',
            'totalEnBd',
            'totalImportaciones'
        ));
    }

    public function show(EventoCecoco $eventoCecoco)
    {
        $eventoCecoco->load('importacion');
        return view('eventos-cecoco.show', compact('eventoCecoco'));
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

    public function exportarCsv(Request $request)
    {
        $query = EventoCecoco::query();

        if ($request->filled('anio')) {
            $query->delAnio((int)$request->anio);
        }

        if ($request->filled('mes')) {
            $query->delMes((int)$request->mes);
        }

        if ($request->filled('operador')) {
            $query->porOperador($request->operador);
        }

        if ($request->filled('tipo')) {
            $query->porTipo($request->tipo);
        }

        if ($request->filled('desde') && $request->filled('hasta')) {
            $desdeCompleto = $request->desde . ' ' . ($request->filled('hora_desde') ? $request->hora_desde : '00:00:00');
            $hastaCompleto = $request->hasta . ' ' . ($request->filled('hora_hasta') ? $request->hora_hasta : '23:59:59');
            $query->whereBetween('fecha_hora', [$desdeCompleto, $hastaCompleto]);
        }

        if ($request->filled('buscar')) {
            $query->buscar($request->buscar);
        }

        $filename = 'cecoco_eventos_' . now()->format('Ymd_His') . '.csv';

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
            'Content-Type' => 'text/csv; charset=UTF-8',
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
            $query->delAnio((int)$request->anio);
        }

        if ($request->filled('mes')) {
            $query->delMes((int)$request->mes);
        }

        if ($request->filled('tipo')) {
            $query->porTipo($request->tipo);
        }

        $eventos = $query->orderBy('fecha_hora', 'desc')->paginate(100);

        return response()->json($eventos);
    }
}
