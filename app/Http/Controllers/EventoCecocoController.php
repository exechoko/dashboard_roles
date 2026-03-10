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

        $anios = Cache::remember('cecoco_anios', 3600, function () {
            return EventoCecoco::distinct()->orderByDesc('anio')->pluck('anio');
        });

        $tipos = Cache::remember('cecoco_tipos', 3600, function () {
            return EventoCecoco::distinct()->orderBy('tipo_servicio')->pluck('tipo_servicio');
        });

        $operadores = Cache::remember('cecoco_operadores', 3600, function () {
            return EventoCecoco::distinct()->orderBy('operador')->limit(300)->pluck('operador');
        });

        $meses = [];
        if ($request->filled('anio')) {
            $meses = Cache::remember('cecoco_meses_' . $request->anio, 3600, function () use ($request) {
                return EventoCecoco::where('anio', $request->anio)
                    ->distinct()
                    ->orderBy('mes')
                    ->pluck('mes');
            });
        }

        $totalEnBd = Cache::remember('cecoco_total_bd', 300, function () {
            return EventoCecoco::count();
        });

        $totalImportaciones = Cache::remember('cecoco_total_importaciones', 300, function () {
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
        $importaciones = Importacion::orderByDesc('created_at')->paginate(20);

        $totalArchivosImportados = Importacion::where('estado', 'completado')->count();
        $totalRegistrosEnBd = EventoCecoco::count();

        $aniosCounts = Importacion::where('estado', 'completado')
            ->selectRaw('anio, COUNT(*) as total')
            ->groupBy('anio')
            ->orderByDesc('anio')
            ->pluck('total', 'anio');

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
            'archivo' => 'required|file|mimes:xls,xlsx,xml|max:102400',
        ]);

        $resultado = $this->parser->procesar($request->file('archivo'));
        $importacion = $resultado['importacion'];

        Cache::forget('cecoco_anios');
        Cache::forget('cecoco_tipos');
        Cache::forget('cecoco_operadores');
        Cache::forget('cecoco_total_bd');
        Cache::forget('cecoco_total_importaciones');

        if ($importacion->anio) {
            Cache::forget('cecoco_meses_' . $importacion->anio);
        }

        $mensaje = "✅ {$importacion->registros_importados} registros nuevos importados.";

        if ($importacion->registros_duplicados > 0) {
            $mensaje .= " ⏭️ {$importacion->registros_duplicados} ya existían y fueron omitidos.";
        }

        if ($importacion->registros_omitidos > 0) {
            $mensaje .= " ⚠️ {$importacion->registros_omitidos} filas con datos insuficientes.";
        }

        $mensaje .= " ⏱️ Procesado en {$importacion->tiempo_procesamiento} segundos.";

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
