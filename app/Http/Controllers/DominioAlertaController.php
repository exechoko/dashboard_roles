<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDominioAlertaRequest;
use App\Http\Requests\UpdateDominioAlertaRequest;
use App\Imports\DominioAlertaImport;
use App\Models\Camara;
use App\Models\DominioAlerta;
use App\Services\AlertaVideoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class DominioAlertaController extends Controller
{
    public function __construct(private AlertaVideoService $service)
    {
        $this->middleware('permission:ver-alerta-dominio|crear-alerta-dominio|editar-alerta-dominio|borrar-alerta-dominio', ['only' => ['index', 'show']]);
        $this->middleware('permission:crear-alerta-dominio', ['only' => ['create', 'store', 'importarForm', 'importar', 'buscarCoincidencias']]);
        $this->middleware('permission:editar-alerta-dominio', ['only' => ['edit', 'update', 'cambiarActivo', 'comentario']]);
        $this->middleware('permission:borrar-alerta-dominio', ['only' => ['destroy']]);
    }

    public function index(Request $request): View
    {
        $query = DominioAlerta::query();

        if ($request->filled('busqueda')) {
            $busqueda = $request->busqueda;
            $query->where(function ($q) use ($busqueda) {
                $q->where('dominio', 'like', "%{$busqueda}%")
                    ->orWhere('marca', 'like', "%{$busqueda}%")
                    ->orWhere('modelo', 'like', "%{$busqueda}%")
                    ->orWhere('motivo', 'like', "%{$busqueda}%");
            });
        }

        $activo = $request->query('activo', '1');

        if ($activo === '1') {
            $query->where('activo', true);
        } elseif ($activo === '0') {
            $query->where('activo', false);
        }

        $dominios = $query->orderByDesc('id')->paginate(15)->withQueryString();

        $contadores = [
            'total' => DominioAlerta::count(),
            'activos' => DominioAlerta::activos()->count(),
            'inactivos' => DominioAlerta::inactivos()->count(),
        ];

        return view('alertas-video.dominios.index', compact('dominios', 'contadores'));
    }

    public function create(): View
    {
        $camaras = $this->camarasDisponibles();

        return view('alertas-video.dominios.crear', compact('camaras'));
    }

    public function buscarCoincidencias(Request $request): JsonResponse
    {
        $request->validate([
            'dominio' => 'nullable|string|max:15',
        ]);

        $coincidencias = DominioAlerta::buscarCoincidencias($request->query('dominio'))
            ->map(fn (DominioAlerta $dominio) => [
                'id' => $dominio->id,
                'dominio' => $dominio->dominio,
                'parcial' => $dominio->parcial,
                'marca' => $dominio->marca,
                'modelo' => $dominio->modelo,
                'motivo' => $dominio->motivo,
                'activo' => $dominio->activo,
                'estado_label' => $dominio->estado_label,
                'url_show' => route('alertas-video.dominios.show', $dominio),
                'url_edit' => route('alertas-video.dominios.edit', $dominio),
            ])
            ->values();

        return response()->json(['coincidencias' => $coincidencias]);
    }

    public function store(StoreDominioAlertaRequest $request): RedirectResponse
    {
        $datos = $request->validated();
        $datos['parcial'] = $request->boolean('parcial');

        $this->service->crear(DominioAlerta::class, $datos);

        return redirect()->route('alertas-video.dominios.index')->with('success', 'Dominio cargado correctamente.');
    }

    public function show(DominioAlerta $dominioAlerta): View
    {
        $dominioAlerta->load(['movimientos.usuario', 'creadoPor', 'actualizadoPor']);

        return view('alertas-video.dominios.show', compact('dominioAlerta'));
    }

    public function edit(DominioAlerta $dominioAlerta): View
    {
        $camaras = $this->camarasDisponibles();

        return view('alertas-video.dominios.editar', compact('dominioAlerta', 'camaras'));
    }

    public function update(UpdateDominioAlertaRequest $request, DominioAlerta $dominioAlerta): RedirectResponse
    {
        $datos = $request->validated();
        $datos['parcial'] = $request->boolean('parcial');

        $this->service->actualizar($dominioAlerta, $datos);

        return redirect()->route('alertas-video.dominios.show', $dominioAlerta)->with('success', 'Dominio actualizado correctamente.');
    }

    public function destroy(Request $request, DominioAlerta $dominioAlerta): RedirectResponse
    {
        $request->validate([
            'motivo_eliminacion' => 'required|string|min:10|max:500',
        ], [
            'motivo_eliminacion.required' => 'Debe proporcionar un motivo para la eliminación.',
            'motivo_eliminacion.min' => 'El motivo debe tener al menos 10 caracteres.',
        ]);

        $this->service->eliminar($dominioAlerta, $request->motivo_eliminacion);

        return redirect()->route('alertas-video.dominios.index')->with('success', 'Dominio eliminado correctamente.');
    }

    public function cambiarActivo(Request $request, DominioAlerta $dominioAlerta): RedirectResponse
    {
        $request->validate([
            'activo' => 'required|boolean',
            'comentario' => 'nullable|string|max:500',
        ]);

        $this->service->cambiarActivo($dominioAlerta, $request->boolean('activo'), $request->comentario);

        return redirect()->route('alertas-video.dominios.show', $dominioAlerta)->with('success', 'Estado actualizado correctamente.');
    }

    public function comentario(Request $request, DominioAlerta $dominioAlerta): RedirectResponse
    {
        $request->validate([
            'comentario' => 'required|string|min:3|max:500',
        ], [
            'comentario.required' => 'El comentario es obligatorio.',
        ]);

        $this->service->agregarComentario($dominioAlerta, $request->comentario);

        return redirect()->route('alertas-video.dominios.show', $dominioAlerta)->with('success', 'Nota agregada correctamente.');
    }

    public function importarForm(): View
    {
        return view('alertas-video.dominios.importar');
    }

    public function importar(Request $request): RedirectResponse
    {
        $request->validate([
            'archivo' => 'required|file|mimes:xlsx,xls',
        ], [
            'archivo.required' => 'Debe seleccionar un archivo.',
            'archivo.mimes' => 'El archivo debe ser de tipo Excel (.xlsx o .xls).',
        ]);

        try {
            $import = app(DominioAlertaImport::class);
            Excel::import($import, $request->file('archivo'));

            $mensaje = "Importación completada. {$import->getCreated()} dominios creados, {$import->getOmitidos()} omitidos (ya existían).";

            if (count($import->getErrors()) > 0) {
                $mensaje .= ' Errores: ' . implode(', ', array_slice($import->getErrors(), 0, 5));
            }

            return redirect()->route('alertas-video.dominios.index')->with('success', $mensaje);
        } catch (\Exception $e) {
            return redirect()->route('alertas-video.dominios.importar')->with('error', 'Error al importar: ' . $e->getMessage());
        }
    }

    /**
     * @return Collection<int, string>
     */
    private function camarasDisponibles(): Collection
    {
        return Camara::whereNull('fecha_desintalacion')
            ->whereNotNull('nombre')
            ->orderBy('nombre')
            ->pluck('nombre')
            ->unique()
            ->values();
    }
}
