<?php

namespace App\Http\Controllers\Movil;

use App\Http\Controllers\Controller;
use App\Models\EntregaEquipo;
use Illuminate\Contracts\View\View;

class EntregaEquipoController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:ver-entrega-equipos');
    }

    public function index(): View
    {
        $entregas = EntregaEquipo::whereIn('estado', ['entregado', 'devolucion_parcial'])
            ->orderBy('fecha_entrega', 'desc')
            ->get()
            ->map(function (EntregaEquipo $entrega) {
                $entrega->equipos_pendientes = $entrega->equiposPendientes()->with('equipo')->get();

                return $entrega;
            })
            ->filter(fn(EntregaEquipo $entrega) => $entrega->equipos_pendientes->isNotEmpty())
            ->values();

        return view('movil.entregas-equipos.index', compact('entregas'));
    }
}
