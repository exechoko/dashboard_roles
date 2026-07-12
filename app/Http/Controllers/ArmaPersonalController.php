<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreArmaPersonalRequest;
use App\Http\Requests\UpdateArmaPersonalRequest;
use App\Models\ArmaTipo;
use App\Models\Personal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ArmaPersonalController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:ver-personal|crear-personal|editar-personal|borrar-personal|restaurar-personal', ['only' => ['index', 'show']]);
        $this->middleware('permission:crear-personal', ['only' => ['create', 'store']]);
        $this->middleware('permission:editar-personal', ['only' => ['edit', 'update']]);
        $this->middleware('permission:borrar-personal', ['only' => ['destroy']]);
        $this->middleware('permission:restaurar-personal', ['only' => ['restore']]);
    }

    public function index(Request $request): View
    {
        $busqueda = $request->input('busqueda');
        $ver_eliminados = $request->input('ver_eliminados', 'activos');

        $query = Personal::query();

        if ($busqueda) {
            $query->where(function ($q) use ($busqueda) {
                $q->where('apellido', 'like', "%{$busqueda}%")
                   ->orWhere('nombre', 'like', "%{$busqueda}%")
                   ->orWhere('lp', 'like', "%{$busqueda}%");
            });
        }

        if ($ver_eliminados === 'eliminados') {
            $query->onlyTrashed();
        } elseif ($ver_eliminados === 'todos') {
            $query->withTrashed();
        }

        $personales = $query->orderBy('apellido')->orderBy('nombre')->paginate(15);

        return view('arma-personal.index', compact('personales', 'busqueda', 'ver_eliminados'));
    }

    public function show(Request $request, Personal $personal): View
    {
        $personal->load(['creadoPor', 'actualizadoPor', 'tipoArma', 'armasAnteriores.tipoArma', 'armasAnteriores.creadoPor']);

        $retencionesQuery = $personal->retenciones()->with(['motivo', 'creadoPor']);

        $estadoFiltro = $request->input('estado');
        if ($estadoFiltro) {
            $retencionesQuery->where('estado', $estadoFiltro);
        }

        $personal->setRelation('retenciones', $retencionesQuery->orderByDesc('fecha_posesion')->get());

        return view('arma-personal.show', compact('personal', 'estadoFiltro'));
    }

    public function create(): View
    {
        $armaTipos = ArmaTipo::activos()->orderBy('nombre')->get();

        return view('arma-personal.crear', compact('armaTipos'));
    }

    public function store(StoreArmaPersonalRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = auth()->id();

        Personal::create($data);

        return redirect()->route('armas.personal.index')->with('success', 'Funcionario creado correctamente.');
    }

    public function edit(Personal $personal): View
    {
        $armaTipos = ArmaTipo::activos()->orderBy('nombre')->get();

        return view('arma-personal.editar', compact('personal', 'armaTipos'));
    }

    public function update(UpdateArmaPersonalRequest $request, Personal $personal): RedirectResponse
    {
        $data = $request->validated();
        $data['updated_by'] = auth()->id();

        if (!empty($data['cambiar_arma']) && !empty($data['numeracion_arma']) && !empty($data['arma_tipo_id'])) {
            $personal->cambiarArma(
                $data['numeracion_arma'],
                $data['arma_tipo_id'],
                $data['nro_chaleco'] ?? null,
                now()->toDateString(),
                $data['motivo_cambio'] ?? 'Cambio por administración'
            );
        }

        $personal->update([
            'jerarquia' => $data['jerarquia'],
            'updated_by' => auth()->id(),
        ]);

        return redirect()->route('armas.personal.index')->with('success', 'Funcionario actualizado correctamente.');
    }

    public function destroy(Personal $personal): RedirectResponse
    {
        $personal->delete();

        return redirect()->route('armas.personal.index')->with('success', 'Funcionario eliminado correctamente.');
    }

    public function restore(int $id): RedirectResponse
    {
        $personal = Personal::onlyTrashed()->findOrFail($id);
        $personal->restore();

        return redirect()->route('armas.personal.index')->with('success', 'Funcionario restaurado correctamente.');
    }
}
