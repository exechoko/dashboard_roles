<?php

namespace App\Http\Controllers\Movil;

use App\Http\Controllers\Controller;
use App\Models\TareaItem;
use Illuminate\Contracts\View\View;

class TareaController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:ver-tarea|crear-tarea|editar-tarea|borrar-tarea')->only('index');
    }

    public function index(): View
    {
        $tareas = TareaItem::with('tarea')
            ->whereDate('fecha_programada', today())
            ->whereIn('estado', [TareaItem::ESTADO_PENDIENTE, TareaItem::ESTADO_EN_PROCESO])
            ->orderBy('fecha_programada', 'asc')
            ->get();

        return view('movil.tareas.index', compact('tareas'));
    }
}
