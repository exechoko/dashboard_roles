<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWebDependenciaRequest;
use App\Http\Requests\UpdateWebDependenciaRequest;
use App\Models\Auditoria;
use App\Models\WebDependencia;
use App\Services\GeneradorDependenciasJs;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Throwable;

class WebDependenciaController extends Controller
{
    public function __construct(private GeneradorDependenciasJs $generador)
    {
        $this->middleware('permission:editar-web-dependencias');
    }

    public function index(): View
    {
        $dependencias = WebDependencia::query()
            ->orderBy('orden')
            ->orderBy('id')
            ->paginate(20);

        return view('web-dependencias.index', [
            'dependencias' => $dependencias,
            'categorias'   => config('landing.dependencias_categorias', []),
        ]);
    }

    public function create(): View
    {
        return view('web-dependencias.crear', [
            'categorias' => config('landing.dependencias_categorias', []),
        ]);
    }

    public function store(StoreWebDependenciaRequest $request): RedirectResponse
    {
        $dependencia = WebDependencia::create($this->datos($request->validated()));

        $this->regenerar();
        $this->auditar($dependencia, 'crear');

        return redirect()->route('web-dependencias.index')->with('success', 'Dependencia creada y publicada en la web.');
    }

    public function edit(WebDependencia $dependencia): View
    {
        return view('web-dependencias.editar', [
            'dependencia' => $dependencia,
            'categorias'  => config('landing.dependencias_categorias', []),
        ]);
    }

    public function update(UpdateWebDependenciaRequest $request, WebDependencia $dependencia): RedirectResponse
    {
        $dependencia->update($this->datos($request->validated()));

        $this->regenerar();
        $this->auditar($dependencia, 'editar');

        return redirect()->route('web-dependencias.index')->with('success', 'Dependencia actualizada.');
    }

    public function destroy(WebDependencia $dependencia): RedirectResponse
    {
        $this->auditar($dependencia, 'eliminar');
        $dependencia->delete();
        $this->regenerar();

        return redirect()->route('web-dependencias.index')->with('success', 'Dependencia eliminada.');
    }

    /**
     * Normaliza los datos: convierte teléfonos y tags de texto a listas.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function datos(array $validated): array
    {
        return [
            'nombre'    => $validated['nombre'],
            'categoria' => $validated['categoria'],
            'direccion' => $validated['direccion'] ?? null,
            'telefonos' => $this->aLista($validated['telefonos'] ?? ''),
            'tags'      => $this->aLista($validated['tags'] ?? ''),
            'orden'     => (int) ($validated['orden'] ?? 0),
        ];
    }

    /**
     * Convierte una cadena separada por comas en una lista limpia.
     *
     * @return array<int, string>
     */
    private function aLista(?string $valor): array
    {
        return collect(explode(',', (string) $valor))
            ->map(fn ($v) => trim($v))
            ->filter()
            ->values()
            ->all();
    }

    private function regenerar(): void
    {
        try {
            $this->generador->generar();
        } catch (Throwable $e) {
            session()->flash('error', 'Se guardó pero no se pudo actualizar la web: ' . $e->getMessage());
        }
    }

    private function auditar(WebDependencia $dependencia, string $accion): void
    {
        Auditoria::create([
            'user_id'      => Auth::id(),
            'nombre_tabla' => 'web_dependencias',
            'accion'       => $accion,
            'cambios'      => json_encode(['id' => $dependencia->id, 'nombre' => $dependencia->nombre], JSON_UNESCAPED_UNICODE),
        ]);
    }
}
