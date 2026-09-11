<?php

namespace App\Http\Controllers\Movil;

use App\Http\Controllers\Controller;
use App\Models\EntregaBodycam;
use Illuminate\Contracts\View\View;

class EntregaBodycamController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:ver-entrega-bodycams');
    }

    public function index(): View
    {
        $entregas = EntregaBodycam::with(['bodycams', 'devoluciones.bodycams'])
            ->activas()
            ->conDevolucionEsperada()
            ->orderBy('fecha_entrega', 'desc')
            ->get()
            ->map(function (EntregaBodycam $entrega) {
                $entrega->bodycams_pendientes = $entrega->bodycamsPendientes()->count();

                return $entrega;
            })
            ->filter(fn(EntregaBodycam $entrega) => $entrega->bodycams_pendientes > 0)
            ->values();

        return view('movil.entregas-bodycams.index', compact('entregas'));
    }
}
