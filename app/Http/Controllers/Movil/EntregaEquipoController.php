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
        $entregas = EntregaEquipo::with(['equipos', 'devoluciones.equipos'])
            ->whereIn('estado', ['entregado', 'devolucion_parcial'])
            ->orderBy('fecha_entrega', 'desc')
            ->get()
            ->map(function (EntregaEquipo $entrega) {
                $equiposDevueltos = $entrega->devoluciones->pluck('equipos')->flatten()->pluck('id')->unique()->count();
                $entrega->equipos_pendientes = $entrega->equipos->count() - $equiposDevueltos;

                return $entrega;
            })
            ->filter(fn(EntregaEquipo $entrega) => $entrega->equipos_pendientes > 0)
            ->values();

        return view('movil.entregas-equipos.index', compact('entregas'));
    }
}
