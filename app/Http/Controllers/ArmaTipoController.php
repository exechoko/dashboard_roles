<?php

namespace App\Http\Controllers;

use App\Models\ArmaTipo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ArmaTipoController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:ver-arma-tipo|crear-arma-tipo|editar-arma-tipo|borrar-arma-tipo', ['only' => ['index']]);
        $this->middleware('permission:crear-arma-tipo', ['only' => ['create', 'store']]);
        $this->middleware('permission:editar-arma-tipo', ['only' => ['edit', 'update']]);
        $this->middleware('permission:borrar-arma-tipo', ['only' => ['destroy']]);
    }

    public function index(): View
    {
        $armaTipos = ArmaTipo::orderBy('nombre')->paginate(15);

        return view('arma-tipos.index', compact('armaTipos'));
    }

    public function create(): View
    {
        return view('arma-tipos.crear');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:100|unique:arma_tipos,nombre',
        ], [
            'nombre.required' => 'El nombre del tipo de arma es obligatorio.',
            'nombre.max'      => 'El nombre no puede superar los 100 caracteres.',
            'nombre.unique'   => 'Ya existe un tipo de arma con ese nombre.',
        ]);

        ArmaTipo::create($validated);

        return redirect()->route('armas.tipos.index')->with('success', 'Tipo de arma creado correctamente.');
    }

    public function edit(ArmaTipo $armaTipo): View
    {
        return view('arma-tipos.editar', compact('armaTipo'));
    }

    public function update(Request $request, ArmaTipo $armaTipo): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:100|unique:arma_tipos,nombre,' . $armaTipo->id,
            'activo' => 'nullable|boolean',
        ], [
            'nombre.required' => 'El nombre del tipo de arma es obligatorio.',
            'nombre.max'      => 'El nombre no puede superar los 100 caracteres.',
            'nombre.unique'   => 'Ya existe un tipo de arma con ese nombre.',
        ]);

        $armaTipo->update($validated);

        return redirect()->route('armas.tipos.index')->with('success', 'Tipo de arma actualizado correctamente.');
    }

    public function destroy(ArmaTipo $armaTipo): RedirectResponse
    {
        $armaTipo->update(['activo' => false]);

        return redirect()->route('armas.tipos.index')->with('success', 'Tipo de arma desactivado correctamente.');
    }
}
