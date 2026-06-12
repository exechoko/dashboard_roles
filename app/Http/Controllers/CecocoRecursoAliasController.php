<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCecocoRecursoAliasRequest;
use App\Http\Requests\UpdateCecocoRecursoAliasRequest;
use App\Models\CecocoRecursoAlias;
use App\Models\Equipo;
use App\Models\Recurso;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CecocoRecursoAliasController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:ver-recurso-alias-cecoco|crear-recurso-alias-cecoco|editar-recurso-alias-cecoco|borrar-recurso-alias-cecoco')->only('index');
        $this->middleware('permission:crear-recurso-alias-cecoco')->only(['create', 'store']);
        $this->middleware('permission:editar-recurso-alias-cecoco')->only(['edit', 'update']);
        $this->middleware('permission:borrar-recurso-alias-cecoco')->only('destroy');
    }

    public function index(Request $request): View
    {
        $texto = trim((string) $request->get('texto'));
        $estado = $request->get('estado');

        $aliases = CecocoRecursoAlias::query()
            ->with(['recurso.flotaActiva.equipo', 'equipo'])
            ->when($texto, function ($query) use ($texto) {
                $query->where(function ($subQuery) use ($texto) {
                    $subQuery->where('alias_cecoco', 'LIKE', "%{$texto}%")
                        ->orWhere('observaciones', 'LIKE', "%{$texto}%")
                        ->orWhereHas('recurso', function ($recursoQuery) use ($texto) {
                            $recursoQuery->where('nombre', 'LIKE', "%{$texto}%");
                        })
                        ->orWhereHas('recurso.flotaActiva.equipo', function ($equipoQuery) use ($texto) {
                            $equipoQuery->where('issi', 'LIKE', "%{$texto}%")
                                ->orWhere('nombre_issi', 'LIKE', "%{$texto}%")
                                ->orWhere('tei', 'LIKE', "%{$texto}%");
                        })
                        ->orWhereHas('equipo', function ($equipoQuery) use ($texto) {
                            $equipoQuery->where('issi', 'LIKE', "%{$texto}%")
                                ->orWhere('nombre_issi', 'LIKE', "%{$texto}%")
                                ->orWhere('tei', 'LIKE', "%{$texto}%");
                        });
                });
            })
            ->when($estado !== null && $estado !== '', function ($query) use ($estado) {
                $query->where('activo', (bool) $estado);
            })
            ->orderBy('alias_cecoco')
            ->paginate(100)
            ->withQueryString();

        return view('cecoco.recursos-alias.index', compact('aliases', 'texto', 'estado'));
    }

    public function create(): View
    {
        return view('cecoco.recursos-alias.crear', $this->formData());
    }

    public function store(StoreCecocoRecursoAliasRequest $request): RedirectResponse
    {
        CecocoRecursoAlias::create($request->validated());

        return redirect()
            ->route('cecoco.recursos-alias.index')
            ->with('success', 'Mapeo CECOCO-CAR911 creado correctamente.');
    }

    public function show(CecocoRecursoAlias $cecocoRecursoAlias): RedirectResponse
    {
        return redirect()->route('cecoco.recursos-alias.edit', $cecocoRecursoAlias);
    }

    public function edit(CecocoRecursoAlias $cecocoRecursoAlias): View
    {
        return view('cecoco.recursos-alias.editar', array_merge(
            ['alias' => $cecocoRecursoAlias],
            $this->formData()
        ));
    }

    public function update(UpdateCecocoRecursoAliasRequest $request, CecocoRecursoAlias $cecocoRecursoAlias): RedirectResponse
    {
        $cecocoRecursoAlias->update($request->validated());

        return redirect()
            ->route('cecoco.recursos-alias.index')
            ->with('success', 'Mapeo CECOCO-CAR911 actualizado correctamente.');
    }

    public function destroy(CecocoRecursoAlias $cecocoRecursoAlias): RedirectResponse
    {
        $cecocoRecursoAlias->delete();

        return redirect()
            ->route('cecoco.recursos-alias.index')
            ->with('success', 'Mapeo CECOCO-CAR911 eliminado correctamente.');
    }

    /**
     * @return array{recursos: \Illuminate\Database\Eloquent\Collection<int, Recurso>, equipos: \Illuminate\Database\Eloquent\Collection<int, Equipo>}
     */
    private function formData(): array
    {
        return [
            'recursos' => Recurso::query()
                ->orderBy('nombre')
                ->get(['id', 'nombre']),
            'equipos' => Equipo::query()
                ->orderBy('issi')
                ->get(['id', 'issi', 'nombre_issi', 'tei']),
        ];
    }
}
