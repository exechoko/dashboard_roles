<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActualizarActivacionTotemRequest;
use App\Models\ActivacionTotem;
use App\Models\Camara;
use App\Services\DetectorActivacionesTotem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivacionTotemController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:ver-activacion-totem')->only(['index']);
        $this->middleware('permission:editar-activacion-totem')->only(['update', 'descartar', 'escanear', 'eliminar']);
    }

    public function index(Request $request): View
    {
        $query = ActivacionTotem::with(['evento', 'camara', 'descargadoPor', 'eliminadoPor']);

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->boolean('vencidas')) {
            $query->vencidas();
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha_evento', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha_evento', '<=', $request->fecha_hasta);
        }

        $activaciones = $query->orderBy('fecha_evento', 'desc')->paginate(15)->withQueryString();

        $estados = ActivacionTotem::ESTADOS;
        $totems = Camara::whereHas('tipoCamara', function ($q) {
            $q->where('tipo', 'BDE (Totem)');
        })->orderBy('nombre')->get();

        return view('activaciones-totem.index', compact('activaciones', 'estados', 'totems'));
    }

    public function update(ActualizarActivacionTotemRequest $request, ActivacionTotem $activacionTotem): RedirectResponse
    {
        $activacionTotem->update([
            'camara_id' => $request->validated('camara_id'),
            'observaciones' => $request->validated('observaciones'),
            'estado' => ActivacionTotem::ESTADO_DESCARGADO,
            'descargado_por' => auth()->id(),
            'fecha_descarga' => now(),
        ]);

        return redirect()->route('activaciones-totem.index')
            ->with('success', 'Descarga registrada correctamente.');
    }

    public function descartar(ActivacionTotem $activacionTotem): RedirectResponse
    {
        $activacionTotem->update([
            'estado' => ActivacionTotem::ESTADO_DESCARTADO,
        ]);

        return redirect()->route('activaciones-totem.index')
            ->with('success', 'Activación descartada.');
    }

    public function escanear(DetectorActivacionesTotem $detector): RedirectResponse
    {
        $creadas = $detector->detectar();

        return redirect()->route('activaciones-totem.index')
            ->with('success', "Escaneo completado: {$creadas} activación(es) nueva(s) detectada(s).");
    }

    public function eliminar(ActivacionTotem $activacionTotem): RedirectResponse
    {
        $activacionTotem->update([
            'estado' => ActivacionTotem::ESTADO_ELIMINADO,
            'eliminado_por' => auth()->id(),
            'fecha_eliminado' => now(),
        ]);

        return redirect()->route('activaciones-totem.index')
            ->with('success', 'Video marcado como eliminado.');
    }
}
