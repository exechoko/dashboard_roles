<?php

namespace App\Http\Controllers\Movil;

use App\Http\Controllers\Controller;
use App\Models\ActivacionTotem;
use App\Models\EntregaBodycam;
use App\Models\EntregaEquipo;
use App\Models\TareaItem;
use Illuminate\Contracts\View\View;

class InicioController extends Controller
{
    public function index(): View
    {
        $usuario = auth()->user();

        $cantEquiposEntregados = 0;
        if ($usuario?->can('ver-entrega-equipos')) {
            $entregasEquiposActivas = EntregaEquipo::with(['equipos', 'devoluciones.equipos'])
                ->whereIn('estado', ['entregado', 'devolucion_parcial'])
                ->get();

            foreach ($entregasEquiposActivas as $entrega) {
                $equiposDevueltos = $entrega->devoluciones->pluck('equipos')->flatten()->pluck('id')->unique()->count();
                $cantEquiposEntregados += $entrega->equipos->count() - $equiposDevueltos;
            }
        }

        $cantBodycamsEntregadas = 0;
        if ($usuario?->can('ver-entrega-bodycams')) {
            $entregasBodycamsActivas = EntregaBodycam::with(['bodycams', 'devoluciones.bodycams'])
                ->activas()
                ->conDevolucionEsperada()
                ->get()
                ->filter(fn($entrega) => $entrega->bodycamsPendientes()->count() > 0);

            $cantBodycamsEntregadas = $entregasBodycamsActivas->sum(fn($entrega) => $entrega->bodycamsPendientes()->count());
        }

        $cantTareasHoy = 0;
        if ($usuario?->canAny(['ver-tarea', 'crear-tarea', 'editar-tarea', 'borrar-tarea'])) {
            $cantTareasHoy = TareaItem::whereDate('fecha_programada', today())
                ->whereIn('estado', [TareaItem::ESTADO_PENDIENTE, TareaItem::ESTADO_EN_PROCESO])
                ->count();
        }

        $cantActivacionesTotemPendientes = 0;
        $cantActivacionesTotemVencidas = 0;
        if ($usuario?->can('ver-activacion-totem')) {
            $cantActivacionesTotemPendientes = ActivacionTotem::where('estado', ActivacionTotem::ESTADO_PENDIENTE)->count();
            $cantActivacionesTotemVencidas = ActivacionTotem::vencidas()->count();
        }

        return view('movil.inicio', compact(
            'cantEquiposEntregados',
            'cantBodycamsEntregadas',
            'cantTareasHoy',
            'cantActivacionesTotemPendientes',
            'cantActivacionesTotemVencidas'
        ));
    }
}
