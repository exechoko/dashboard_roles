<?php
// app/Http/Controllers/PatrimonioBienController.php

namespace App\Http\Controllers;

use App\Models\PatrimonioBien;
use App\Models\PatrimonioTipoBien;
use App\Models\Destino;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PatrimonioBienController extends Controller
{
    public function index(Request $request)
    {
        $query = PatrimonioBien::with(['tipoBien', 'destino', 'ultimoMovimiento']);

        // Filtros
        if ($request->filled('tipo_bien_id')) {
            $query->where('tipo_bien_id', $request->tipo_bien_id);
        }

        if ($request->filled('destino_id')) {
            $query->where('destino_id', $request->destino_id);
        }

        if ($request->filled('ubicacion')) {
            $query->where('ubicacion', $request->ubicacion);
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('siaf')) {
            $query->where('siaf', 'like', '%' . $request->siaf . '%');
        }

        if ($request->filled('numero_serie')) {
            $query->where('numero_serie', 'like', '%' . $request->numero_serie . '%');
        }

        if ($request->filled('busqueda')) {
            $busqueda = $request->busqueda;
            $query->where(function ($q) use ($busqueda) {
                $q->where('siaf', 'like', '%' . $busqueda . '%')
                    ->orWhere('numero_serie', 'like', '%' . $busqueda . '%')
                    ->orWhere('descripcion', 'like', '%' . $busqueda . '%');
            });
        }

        $bienes = $query->orderBy('created_at', 'desc')->paginate(15);
        $tiposBien = PatrimonioTipoBien::orderBy('nombre')->get();
        $destinos = Destino::orderBy('nombre')->get();

        return view('patrimonio.bienes.index', compact('bienes', 'tiposBien', 'destinos'));
    }

    public function create()
    {
        $tiposBien = PatrimonioTipoBien::orderBy('nombre')->get();
        $destinos = Destino::orderBy('nombre')->get();

        return view('patrimonio.bienes.create', compact('tiposBien', 'destinos'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tipo_bien_id' => 'required|exists:patrimonio_tipos_bien,id',
            'destino_id' => 'nullable|exists:destinos,id',
            'ubicacion' => 'nullable|string|max:150',
            'siaf' => 'nullable|string|max:100',
            'descripcion' => 'required|string',
            'numero_serie' => 'nullable|string|max:255',
            'fecha_alta' => 'required|date',
            'observaciones' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $validated['estado'] = 'activo';
            $bien = PatrimonioBien::create($validated);

            // Registrar movimiento de alta
            $bien->registrarMovimiento(
                'alta',
                null,
                null,
                $request->destino_id,
                $request->ubicacion,
                'Alta inicial del bien'
            );

            DB::commit();

            return redirect()->route('patrimonio.bienes.show', $bien->id)
                ->with('success', 'Bien registrado exitosamente');
        } catch (Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al registrar el bien: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function show($id)
    {
        $bien = PatrimonioBien::with(['tipoBien', 'destino', 'movimientos.destinoDesde', 'movimientos.destinoHasta'])
            ->findOrFail($id);

        return view('patrimonio.bienes.show', compact('bien'));
    }

    public function edit($id)
    {
        $bien = PatrimonioBien::findOrFail($id);
        $tiposBien = PatrimonioTipoBien::orderBy('nombre')->get();
        $destinos = Destino::orderBy('nombre')->get();

        return view('patrimonio.bienes.edit', compact('bien', 'tiposBien', 'destinos'));
    }

    public function update(Request $request, $id)
    {
        $bien = PatrimonioBien::findOrFail($id);

        $validated = $request->validate([
            'tipo_bien_id' => 'required|exists:patrimonio_tipos_bien,id',
            'destino_id' => 'nullable|exists:destinos,id',
            'ubicacion' => 'nullable|string|max:150',
            'siaf' => 'nullable|string|max:100',
            'descripcion' => 'required|string',
            'numero_serie' => 'nullable|string|max:255',
            'fecha_alta' => 'required|date',
            'observaciones' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Verificar si cambió el destino y la ubicación
            $destinoAnterior = $bien->destino_id;
            $ubicacionAnterior = $bien->ubicacion;
            $destinoNuevo = $request->destino_id;
            $ubicacionNueva = $request->ubicacion;

            if ($destinoAnterior === $destinoNuevo && $ubicacionNueva) {
                // Registrar traslado
                $bien->registrarMovimiento(
                    'traslado',
                    $destinoAnterior,
                    $ubicacionAnterior,
                    $destinoNuevo,
                    $ubicacionNueva,
                    'Traslado registrado desde edición'
                );
            }

            $bien->update($validated);

            DB::commit();

            return redirect()->route('patrimonio.bienes.show', $bien->id)
                ->with('success', 'Bien actualizado exitosamente');
        } catch (Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al actualizar el bien: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $bien = PatrimonioBien::findOrFail($id);
            $bien->delete();

            return redirect()->route('patrimonio.bienes.index')
                ->with('success', 'Bien eliminado exitosamente');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al eliminar el bien: ' . $e->getMessage());
        }
    }

    public function darBaja($id)
    {
        $bien = PatrimonioBien::findOrFail($id);

        return view('patrimonio.bienes.baja', compact('bien'));
    }

    public function procesarBaja(Request $request, $id)
    {
        $validated = $request->validate([
            'tipo_baja' => 'required|in:baja_desuso,baja_transferencia,baja_rotura',
            'observaciones' => 'required|string',
        ]);

        DB::beginTransaction();
        try {
            $bien = PatrimonioBien::findOrFail($id);

            $bien->update([
                'estado' => 'baja',
            ]);

            $bien->registrarMovimiento(
                $validated['tipo_baja'],
                $bien->destino_id,
                null,
                $validated['observaciones']
            );

            DB::commit();

            return redirect()->route('patrimonio.bienes.show', $bien->id)
                ->with('success', 'Baja procesada exitosamente');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al procesar la baja: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function traslado($id)
    {
        $bien = PatrimonioBien::findOrFail($id);
        $destinos = Destino::orderBy('nombre')->get();

        return view('patrimonio.bienes.traslado', compact('bien', 'destinos'));
    }

    public function procesarTraslado(Request $request, $id)
    {
        $validated = $request->validate([
            'destino_hasta_id' => 'required|exists:destinos,id',
            'observaciones' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $bien = PatrimonioBien::findOrFail($id);
            $destinoDesde = $bien->destino_id;
            $destinoHasta = $validated['destino_hasta_id'];

            if ($destinoDesde == $destinoHasta) {
                return back()->with('error', 'El destino de origen y destino son iguales')
                    ->withInput();
            }

            $bien->update([
                'destino_id' => $destinoHasta,
            ]);

            $bien->registrarMovimiento(
                'traslado',
                $destinoDesde,
                $destinoHasta,
                $validated['observaciones']
            );

            DB::commit();

            return redirect()->route('patrimonio.bienes.show', $bien->id)
                ->with('success', 'Traslado procesado exitosamente');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al procesar el traslado: ' . $e->getMessage())
                ->withInput();
        }
    }
}
