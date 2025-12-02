<?php
// app/Http/Controllers/PatrimonioTipoBienController.php

namespace App\Http\Controllers;

use App\Models\PatrimonioTipoBien;
use Exception;
use Illuminate\Http\Request;

class PatrimonioTipoBienController extends Controller
{
    public function index()
    {
        $tipos = PatrimonioTipoBien::withCount('bienes')->orderBy('nombre')->paginate(15);

        return view('patrimonio.tipos-bien.index', compact('tipos'));
    }

    public function create()
    {
        return view('patrimonio.tipos-bien.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:100|unique:patrimonio_tipos_bien,nombre',
            'tiene_tabla_propia' => 'boolean',
            'tabla_referencia' => 'nullable|string|max:100',
            'descripcion' => 'nullable|string',
        ]);

        try {
            PatrimonioTipoBien::create($validated);

            return redirect()->route('patrimonio.tipos-bien.index')
                ->with('success', 'Tipo de bien creado exitosamente');
        } catch (Exception $e) {
            return back()->with('error', 'Error al crear el tipo de bien: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function edit($id)
    {
        $tipo = PatrimonioTipoBien::findOrFail($id);

        return view('patrimonio.tipos-bien.edit', compact('tipo'));
    }

    public function update(Request $request, $id)
    {
        $tipo = PatrimonioTipoBien::findOrFail($id);

        $validated = $request->validate([
            'nombre' => 'required|string|max:100|unique:patrimonio_tipos_bien,nombre,' . $id,
            'tiene_tabla_propia' => 'boolean',
            'tabla_referencia' => 'nullable|string|max:100',
            'descripcion' => 'nullable|string',
        ]);

        try {
            $tipo->update($validated);

            return redirect()->route('patrimonio.tipos-bien.index')
                ->with('success', 'Tipo de bien actualizado exitosamente');
        } catch (Exception $e) {
            return back()->with('error', 'Error al actualizar el tipo de bien: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $tipo = PatrimonioTipoBien::findOrFail($id);

            if ($tipo->bienes()->count() > 0) {
                return back()->with('error', 'No se puede eliminar un tipo de bien con bienes asociados');
            }

            $tipo->delete();

            return redirect()->route('patrimonio.tipos-bien.index')
                ->with('success', 'Tipo de bien eliminado exitosamente');
        } catch (Exception $e) {
            return back()->with('error', 'Error al eliminar el tipo de bien: ' . $e->getMessage());
        }
    }
}
