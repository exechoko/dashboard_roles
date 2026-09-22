<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateArmaPersonalRequest;
use App\Models\ArmaTipo;
use App\Models\Personal;
use App\Models\PersonalLicencia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ArmaPersonalController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:ver-personal|editar-personal', ['only' => ['index', 'show']]);
        $this->middleware('permission:editar-personal', ['only' => ['edit', 'update']]);
    }

    public function index(Request $request): View
    {
        $busqueda = trim((string) $request->input('busqueda', ''));
        $ver_eliminados = $request->input('ver_eliminados', 'activos');
        $estadoLicencia = $request->input('estado_licencia', 'todos');
        $tipoLicencia = $request->input('tipo_licencia');

        if (!in_array($estadoLicencia, ['todos', 'vigentes', 'sin_licencia'], true)) {
            $estadoLicencia = 'todos';
        }

        $tipoLicencia = is_numeric($tipoLicencia) ? (int) $tipoLicencia : null;

        $query = Personal::query();

        if ($busqueda !== '') {
            $query->where(function (Builder $q) use ($busqueda): void {
                $q->where('apellido', 'like', "%{$busqueda}%")
                    ->orWhere('nombre', 'like', "%{$busqueda}%")
                    ->orWhere('lp', 'like', "%{$busqueda}%")
                    ->orWhere('dni', 'like', "%{$busqueda}%")
                    ->orWhere('situacion_personal911', 'like', "%{$busqueda}%")
                    ->orWhere('funcion_personal911', 'like', "%{$busqueda}%")
                    ->orWhere('observaciones_personal911', 'like', "%{$busqueda}%")
                    ->orWhereHas('licencias', function (Builder $licencias) use ($busqueda): void {
                        $licencias->vigentes()
                            ->where(function (Builder $licencia) use ($busqueda): void {
                                $licencia->where('tipo_licencia', 'like', "%{$busqueda}%")
                                    ->orWhere('motivo', 'like', "%{$busqueda}%");
                            });
                    });
            });
        }

        if ($estadoLicencia === 'vigentes' || $tipoLicencia !== null) {
            $query->whereHas('licencias', function (Builder $licencias) use ($tipoLicencia): void {
                $licencias->vigentes();

                if ($tipoLicencia !== null) {
                    $licencias->where('tipo_licencia_id', $tipoLicencia);
                }
            });
        } elseif ($estadoLicencia === 'sin_licencia') {
            $query->whereDoesntHave('licencias', function (Builder $licencias): void {
                $licencias->vigentes();
            });
        }

        if ($ver_eliminados === 'eliminados') {
            $query->onlyTrashed();
        } elseif ($ver_eliminados === 'todos') {
            $query->withTrashed();
        }

        $personales = $query
            ->with('licencias')
            ->orderBy('apellido')
            ->orderBy('nombre')
            ->paginate(15)
            ->withQueryString();

        $tiposLicencia = PersonalLicencia::query()
            ->select(['tipo_licencia_id', 'tipo_licencia'])
            ->whereNotNull('tipo_licencia_id')
            ->whereNotNull('tipo_licencia')
            ->where('tipo_licencia', '<>', '')
            ->distinct()
            ->orderBy('tipo_licencia')
            ->get();

        $totalActivos = Personal::count();
        $totalDeLicencia = Personal::whereHas('licencias', function (Builder $licencias): void {
            $licencias->vigentes();
        })->count();

        return view('arma-personal.index', compact(
            'personales',
            'busqueda',
            'ver_eliminados',
            'estadoLicencia',
            'tipoLicencia',
            'tiposLicencia',
            'totalActivos',
            'totalDeLicencia'
        ));
    }

    public function show(Request $request, Personal $personal): View
    {
        $personal->load([
            'creadoPor',
            'actualizadoPor',
            'tipoArma',
            'armasAnteriores.tipoArma',
            'armasAnteriores.creadoPor',
            'licencias' => fn ($query) => $query->orderByDesc('fecha_inicio')->orderByDesc('id'),
        ]);

        $retencionesQuery = $personal->retenciones()->with(['motivo', 'creadoPor']);

        $estadoFiltro = $request->input('estado');
        if ($estadoFiltro) {
            $retencionesQuery->where('estado', $estadoFiltro);
        }

        $personal->setRelation('retenciones', $retencionesQuery->orderByDesc('fecha_posesion')->get());

        return view('arma-personal.show', compact('personal', 'estadoFiltro'));
    }

    public function edit(Personal $personal): View
    {
        $armaTipos = ArmaTipo::activos()->orderBy('nombre')->get();

        return view('arma-personal.editar', compact('personal', 'armaTipos'));
    }

    public function update(UpdateArmaPersonalRequest $request, Personal $personal): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $personal): void {
            $personal->cambiarArma(
                $data['numeracion_arma'],
                $data['arma_tipo_id'],
                $data['nro_chaleco'] ?? null,
                now()->toDateString(),
                $data['motivo_cambio'],
                $personal->personal911_id !== null,
                auth()->id()
            );
        });

        return redirect()->route('armas.personal.index')->with('success', 'Arma/chaleco corregido correctamente.');
    }
}
