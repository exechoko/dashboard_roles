<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreArmaPersonalRequest;
use App\Http\Requests\UpdateArmaPersonalRequest;
use App\Models\Personal;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ArmaPersonalController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:ver-personal|crear-personal|editar-personal|borrar-personal', ['only' => ['index']]);
        $this->middleware('permission:crear-personal', ['only' => ['create', 'store']]);
        $this->middleware('permission:editar-personal', ['only' => ['edit', 'update']]);
        $this->middleware('permission:borrar-personal', ['only' => ['destroy']]);
    }

    public function index(): View
    {
        $personales = Personal::orderBy('apellido')->orderBy('nombre')->paginate(15);

        return view('arma-personal.index', compact('personales'));
    }

    public function create(): View
    {
        return view('arma-personal.crear');
    }

    public function store(StoreArmaPersonalRequest $request): RedirectResponse
    {
        Personal::create($request->validated());

        return redirect()->route('armas.personal.index')->with('success', 'Funcionario creado correctamente.');
    }

    public function edit(Personal $personal): View
    {
        return view('arma-personal.editar', compact('personal'));
    }

    public function update(UpdateArmaPersonalRequest $request, Personal $personal): RedirectResponse
    {
        $personal->update($request->validated());

        return redirect()->route('armas.personal.index')->with('success', 'Funcionario actualizado correctamente.');
    }

    public function destroy(Personal $personal): RedirectResponse
    {
        $personal->delete();

        return redirect()->route('armas.personal.index')->with('success', 'Funcionario eliminado correctamente.');
    }
}
