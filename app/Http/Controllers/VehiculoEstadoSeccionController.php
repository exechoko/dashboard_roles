<?php

namespace App\Http\Controllers;

use App\Models\Vehiculo;
use App\Models\VehiculoEstadoSeccion;
use Illuminate\Http\Request;

class VehiculoEstadoSeccionController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:gestionar-flota-911');
    }

    public function update(Request $request, Vehiculo $vehiculo)
    {
        $request->validate([
            'estado'        => 'required|in:en_servicio,fuera_de_servicio,en_taller,baja_provisional',
            'observaciones' => 'nullable|string|max:500',
        ], [
            'estado.required' => 'El estado es obligatorio.',
            'estado.in'       => 'Estado inválido.',
        ]);

        VehiculoEstadoSeccion::updateOrCreate(
            ['vehiculo_id' => $vehiculo->id],
            [
                'estado'        => $request->estado,
                'observaciones' => $request->observaciones,
                'user_id'       => auth()->id(),
            ]
        );

        return back()->with('success', 'Estado del vehículo actualizado.');
    }
}
