<?php

namespace App\Http\Controllers\Movil;

use App\Http\Controllers\Controller;
use App\Models\FlotaGeneral;
use App\Models\Historico;
use Illuminate\Http\Request;

class FlotaController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:ver-flota');
    }

    public function index(Request $request)
    {
        $texto = trim((string) $request->get('texto'));

        $flota = FlotaGeneral::with(['equipo.tipo_terminal', 'equipo.estado', 'recurso.vehiculo', 'destino'])
            ->when($texto !== '', function ($query) use ($texto) {
                $query->where(function ($q) use ($texto) {
                    $q->whereHas('equipo', function ($equipo) use ($texto) {
                        $equipo->where('issi', 'like', "%{$texto}%")
                            ->orWhere('tei', 'like', "%{$texto}%")
                            ->orWhere('nombre_issi', 'like', "%{$texto}%");
                    })->orWhereHas('recurso', function ($recurso) use ($texto) {
                        $recurso->where('nombre', 'like', "%{$texto}%");
                    })->orWhereHas('destino', function ($destino) use ($texto) {
                        $destino->where('nombre', 'like', "%{$texto}%");
                    });
                });
            })
            ->orderBy('updated_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        $equipoIds = $flota->getCollection()->pluck('equipo_id')->filter()->unique()->values();

        $ultimosMovimientos = Historico::whereIn('equipo_id', $equipoIds)
            ->with('tipoMovimiento')
            ->orderBy('fecha_asignacion', 'desc')
            ->get()
            ->groupBy('equipo_id')
            ->map(fn($grupo) => $grupo->first());

        $flota->getCollection()->each(function (FlotaGeneral $f) use ($ultimosMovimientos) {
            $f->ultimo_movimiento_calculado = $ultimosMovimientos->get($f->equipo_id);
        });

        return view('movil.flota.index', compact('flota', 'texto'));
    }

    public function show(FlotaGeneral $flota)
    {
        $flota->load(['equipo.tipo_terminal', 'equipo.estado', 'recurso.vehiculo', 'destino', 'cargo', 'destinoPatrimonial']);

        $historico = Historico::where('equipo_id', $flota->equipo_id)
            ->with(['destino', 'recurso', 'tipoMovimiento'])
            ->orderBy('fecha_asignacion', 'desc')
            ->get();

        // Vuelve a la búsqueda tal cual quedó (texto, página), no al listado
        // vacío: solo se confía en la URL anterior si de verdad viene del
        // listado de flota.
        $volver = str_starts_with(url()->previous(), route('movil.flota.index'))
            ? url()->previous()
            : route('movil.flota.index');

        return view('movil.flota.show', compact('flota', 'historico', 'volver'));
    }
}
