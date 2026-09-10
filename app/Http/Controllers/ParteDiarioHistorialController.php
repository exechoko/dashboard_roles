<?php

namespace App\Http\Controllers;

use App\Models\Destino;
use App\Models\ParteDiario;
use App\Models\RecursoEstadoDiario;
use App\Services\ParteDiarioDocxService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Consulta de solo lectura del histórico de partes diarios de Flota 911:
 * qué recursos circularon y quiénes los tripularon en cada fecha/guardia.
 */
class ParteDiarioHistorialController extends Controller
{
    private const DIVISION_911_ID = 42;

    public function __construct()
    {
        $this->middleware('can:ver-historial-parte-diario');
    }

    public function index(Request $request): View
    {
        $seccionIds = $this->seccionIds();

        $desde   = $request->filled('desde') ? $request->date('desde') : null;
        $hasta   = $request->filled('hasta') ? $request->date('hasta') : null;
        $seccion = $request->integer('seccion') ?: null;
        $guardia = $request->input('guardia');

        $partes = ParteDiario::query()
            ->whereIn('destino_id', $seccionIds)
            ->with(['seccion', 'usuario'])
            ->withCount('dotaciones')
            ->when($desde, fn ($q) => $q->whereDate('fecha', '>=', $desde))
            ->when($hasta, fn ($q) => $q->whereDate('fecha', '<=', $hasta))
            ->when($seccion, fn ($q) => $q->where('destino_id', $seccion))
            ->when(
                array_key_exists($guardia, RecursoEstadoDiario::$guardias),
                fn ($q) => $q->where('guardia', $guardia)
            )
            ->orderByDesc('fecha_inicio')
            ->orderBy('destino_id')
            ->paginate(25)
            ->withQueryString();

        $secciones = Destino::whereIn('id', $seccionIds)->orderBy('nombre')->get(['id', 'nombre']);
        $guardias  = RecursoEstadoDiario::$guardias;

        return view('flota-911.partes-diarios.index', compact(
            'partes', 'secciones', 'guardias', 'desde', 'hasta', 'seccion', 'guardia'
        ));
    }

    public function show(ParteDiario $parte): View
    {
        abort_unless(in_array($parte->destino_id, $this->seccionIds(), true), 404);

        $parte->load([
            'seccion',
            'usuario',
            'estadosDiarios.recurso.vehiculo',
            'dotaciones.personal',
            'asignaciones',
        ]);

        $dotacionesPorRecurso = $parte->dotaciones
            ->sortBy('orden')
            ->groupBy('recurso_id');

        $recursos = $parte->estadosDiarios
            ->sortBy(fn ($e) => optional($e->recurso)->nombre)
            ->map(fn ($estado) => [
                'estado'     => $estado,
                'recurso'    => $estado->recurso,
                'tripulacion' => $dotacionesPorRecurso->get($estado->recurso_id, collect()),
            ])
            ->values();

        $novedades = $parte->novedades();

        return view('flota-911.partes-diarios.show', compact('parte', 'recursos', 'novedades'));
    }

    public function descargar(ParteDiario $parte, ParteDiarioDocxService $docxService)
    {
        abort_unless(in_array($parte->destino_id, $this->seccionIds(), true), 404);

        $parte->load([
            'seccion',
            'estadosDiarios.recurso.vehiculo',
            'dotaciones.personal',
            'asignaciones',
        ]);

        return $docxService->generar($parte, $parte->novedades());
    }

    /** @return list<int> */
    private function seccionIds(): array
    {
        return Destino::findOrFail(self::DIVISION_911_ID)
            ->getDestinosHijosRecursivo()
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
