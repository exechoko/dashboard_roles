<?php

namespace App\Http\Controllers;

use App\Models\Destino;
use App\Models\Vehiculo;
use App\Models\VehiculoPrestamo;
use Illuminate\Http\Request;

class VehiculoPrestamoController extends Controller
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

        $prestamos = VehiculoPrestamo::activos()
            ->whereIn('destino_origen_id', $destinoIds)
            ->with(['vehiculo', 'destinoOrigen', 'destinoDestino', 'usuarioPrestamo'])
            ->orderByDesc('fecha_salida')
            ->get();

        $historial = VehiculoPrestamo::where('activo', false)
            ->whereIn('destino_origen_id', $destinoIds)
            ->with(['vehiculo', 'destinoOrigen', 'destinoDestino', 'usuarioPrestamo', 'usuarioRetorno'])
            ->orderByDesc('fecha_retorno')
            ->paginate(20);

        $destinos = Destino::orderBy('nombre')->get();

        return view('flota-911.prestamos.index', compact('prestamos', 'historial', 'destinos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'vehiculo_id'        => 'required|exists:vehiculos,id',
            'destino_origen_id'  => 'required|exists:destino,id',
            'destino_destino_id' => 'required|exists:destino,id|different:destino_origen_id',
            'fecha_salida'       => 'required|date',
            'observaciones_salida' => 'nullable|string|max:1000',
        ], [
            'vehiculo_id.required'        => 'Seleccione un vehículo.',
            'destino_origen_id.required'  => 'Seleccione la sección de origen.',
            'destino_destino_id.required' => 'Seleccione el destino.',
            'destino_destino_id.different' => 'El destino debe ser diferente al origen.',
            'fecha_salida.required'       => 'La fecha de salida es obligatoria.',
        ]);

        VehiculoPrestamo::create([
            'vehiculo_id'          => $request->vehiculo_id,
            'destino_origen_id'    => $request->destino_origen_id,
            'destino_destino_id'   => $request->destino_destino_id,
            'fecha_salida'         => $request->fecha_salida,
            'observaciones_salida' => $request->observaciones_salida,
            'user_id_prestamo'     => auth()->id(),
            'activo'               => true,
        ]);

        return back()->with('success', 'Préstamo registrado correctamente.');
    }

    public function devolver(Request $request, VehiculoPrestamo $prestamo)
    {
        $request->validate([
            'fecha_retorno'          => 'required|date|after_or_equal:' . $prestamo->fecha_salida->toDateString(),
            'observaciones_retorno'  => 'nullable|string|max:1000',
        ], [
            'fecha_retorno.required'        => 'La fecha de retorno es obligatoria.',
            'fecha_retorno.after_or_equal'  => 'La fecha de retorno no puede ser anterior a la de salida.',
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
