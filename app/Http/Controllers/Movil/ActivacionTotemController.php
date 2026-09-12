<?php

namespace App\Http\Controllers\Movil;

use App\Http\Controllers\Controller;
use App\Models\ActivacionTotem;
use Illuminate\Contracts\View\View;

class ActivacionTotemController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:ver-activacion-totem');
    }

    public function index(): View
    {
        $activaciones = ActivacionTotem::where('estado', ActivacionTotem::ESTADO_PENDIENTE)
            ->orderBy('fecha_evento', 'desc')
            ->get();

        return view('movil.activaciones-totem.index', compact('activaciones'));
    }
}
