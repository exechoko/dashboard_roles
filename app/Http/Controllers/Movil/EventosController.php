<?php

namespace App\Http\Controllers\Movil;

use App\Http\Controllers\Controller;
use App\Models\EventoCecoco;
use App\Services\CecocoExpedienteService;
use Illuminate\Http\Request;

class EventosController extends Controller
{
    public function __construct(private CecocoExpedienteService $expedienteService)
    {
        $this->middleware('permission:ver-analizador-eventos-cecoco')->only('index');
    }

    public function index(Request $request)
    {
        $eventos = null;
        $tieneFiltros = $request->hasAny(['buscar', 'desde_datetime', 'hasta_datetime']);

        if ($tieneFiltros) {
            $filtros = $request->only(['buscar', 'desde_datetime', 'hasta_datetime']);

            $eventos = EventoCecoco::select(['id', 'nro_expediente', 'fecha_hora', 'operador', 'direccion', 'tipo_servicio'])
                ->filtrado($filtros)
                ->ordenadoPor($request->input('orden'))
                ->simplePaginate(20)
                ->withQueryString();
        }

        return view('movil.eventos.index', compact('eventos'));
    }

    public function show(Request $request, EventoCecoco $eventoCecoco)
    {
        $this->authorize('ver-expediente-cecoco');

        $eventoCecoco->load('detalle');

        $detalle = null;
        $errorExpediente = null;

        try {
            $detalle = $this->expedienteService->obtenerDetalleExpedienteCacheado($eventoCecoco, $request->boolean('refrescar'));
        } catch (\Throwable $e) {
            $errorExpediente = 'No se pudo obtener el expediente completo: ' . $e->getMessage();
        }

        // Vuelve a la búsqueda tal cual quedó (texto, fechas, página), no al
        // listado vacío: solo se confía en la URL anterior si de verdad viene
        // del listado de eventos.
        $volver = str_starts_with(url()->previous(), route('movil.eventos.index'))
            ? url()->previous()
            : route('movil.eventos.index');

        return view('movil.eventos.show', compact('eventoCecoco', 'detalle', 'errorExpediente', 'volver'));
    }
}
