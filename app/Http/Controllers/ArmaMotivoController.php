<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreArmaMotivoRequest;
use App\Http\Requests\UpdateArmaMotivoRequest;
use App\Models\ArmaMotivo;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ArmaMotivoController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:ver-arma-motivo|crear-arma-motivo|editar-arma-motivo|borrar-arma-motivo', ['only' => ['index']]);
        $this->middleware('permission:crear-arma-motivo', ['only' => ['create', 'store']]);
        $this->middleware('permission:editar-arma-motivo', ['only' => ['edit', 'update']]);
        $this->middleware('permission:borrar-arma-motivo', ['only' => ['destroy']]);
    }

    public function index(): View
    {
        $motivos = ArmaMotivo::orderBy('nombre')->paginate(15);

        return view('arma-motivos.index', compact('motivos'));
    }

    public function create(): View
    {
        return view('arma-motivos.crear');
    }

    public function store(StoreArmaMotivoRequest $request): RedirectResponse
    {
        ArmaMotivo::create($request->validated());

        return redirect()->route('armas.motivos.index')->with('success', 'Motivo creado correctamente.');
    }

    public function edit(ArmaMotivo $armaMotivo): View
    {
        return view('arma-motivos.editar', compact('armaMotivo'));
    }

    public function update(UpdateArmaMotivoRequest $request, ArmaMotivo $armaMotivo): RedirectResponse
    {
        $armaMotivo->update($request->validated());

        return redirect()->route('armas.motivos.index')->with('success', 'Motivo actualizado correctamente.');
    }

    public function destroy(ArmaMotivo $armaMotivo): RedirectResponse
    {
        if ($armaMotivo->retenciones()->exists()) {
            return redirect()->route('armas.motivos.index')->with('error', 'No se puede eliminar el motivo porque tiene retenciones asociadas.');
        }

        $armaMotivo->delete();

        return redirect()->route('armas.motivos.index')->with('success', 'Motivo eliminado correctamente.');
    }
}
