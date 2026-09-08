<?php

namespace App\Http\Controllers;

use App\Models\Destino;
use App\Models\Recurso;
use App\Models\RecursoPrestamo;
use Illuminate\Http\Request;

class RecursoPrestamoController extends Controller
{
    private const DIVISION_911_ID = 42;

    public function __construct()
    {
        $this->middleware('can:gestionar-flota-911');
    }

    public function index()
    {
        $division = Destino::findOrFail(self::DIVISION_911_ID);
        $destinoIds = $division->getDestinosHijosRecursivo();

        $prestamos = RecursoPrestamo::activos()
            ->whereIn('destino_origen_id', $destinoIds)
            ->with(['recurso', 'vehiculoSnapshot', 'destinoOrigen', 'destinoDestino'])
            ->orderByDesc('fecha_salida')
            ->get();

        $historial = RecursoPrestamo::where('activo', false)
            ->whereIn('destino_origen_id', $destinoIds)
            ->with(['recurso', 'vehiculoSnapshot', 'destinoOrigen', 'destinoDestino'])
            ->orderByDesc('fecha_retorno')
            ->paginate(20);

        $destinos = Destino::orderBy('nombre')->get();

        // Recursos con vehículo actual para el selector de préstamo
        $recursosDisponibles = Recurso::whereIn('destino_id', $destinoIds)
            ->with(['asignacionActual.vehiculo', 'vehiculo'])
            ->get()
            ->filter(fn($r) => $r->vehiculoActual() !== null);

        return view('flota-911.prestamos.index', compact('prestamos', 'historial', 'destinos', 'recursosDisponibles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'recurso_id'         => 'required|exists:recursos,id',
            'destino_origen_id'  => 'required|exists:destino,id',
            'destino_destino_id' => 'required|exists:destino,id|different:destino_origen_id',
            'fecha_salida'       => 'required|date',
            'observaciones_salida' => 'nullable|string|max:1000',
        ], [
            'recurso_id.required'         => 'Seleccione un recurso.',
            'destino_origen_id.required'  => 'Seleccione la sección de origen.',
            'destino_destino_id.required' => 'Seleccione el destino.',
            'destino_destino_id.different' => 'El destino debe ser diferente al origen.',
            'fecha_salida.required'       => 'La fecha de salida es obligatoria.',
        ]);

        $recurso = Recurso::findOrFail($request->recurso_id);
        $vehiculoSnapshot = $recurso->vehiculoActual()?->id;

        RecursoPrestamo::create([
            'recurso_id'           => $recurso->id,
            'vehiculo_id_snapshot' => $vehiculoSnapshot,
            'destino_origen_id'    => $request->destino_origen_id,
            'destino_destino_id'   => $request->destino_destino_id,
            'fecha_salida'         => $request->fecha_salida,
            'observaciones_salida' => $request->observaciones_salida,
            'user_id_prestamo'     => auth()->id(),
            'activo'               => true,
        ]);

        return back()->with('success', 'Préstamo registrado correctamente.');
    }

    public function devolver(Request $request, RecursoPrestamo $prestamo)
    {
        $request->validate([
            'fecha_retorno'         => 'required|date|after_or_equal:' . $prestamo->fecha_salida->toDateString(),
            'observaciones_retorno' => 'nullable|string|max:1000',
        ], [
            'fecha_retorno.required'       => 'La fecha de retorno es obligatoria.',
            'fecha_retorno.after_or_equal' => 'La fecha de retorno no puede ser anterior a la de salida.',
        ]);

        $prestamo->update([
            'fecha_retorno'         => $request->fecha_retorno,
            'observaciones_retorno' => $request->observaciones_retorno,
            'user_id_retorno'       => auth()->id(),
            'activo'                => false,
        ]);

        return back()->with('success', 'Devolución registrada correctamente.');
    }
}
