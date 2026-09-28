<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePersonaAlertaRequest;
use App\Http\Requests\UpdatePersonaAlertaRequest;
use App\Imports\PersonaAlertaImport;
use App\Models\PersonaAlerta;
use App\Services\AlertaVideoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class PersonaAlertaController extends Controller
{
    public function __construct(private AlertaVideoService $service)
    {
        $this->middleware('permission:ver-alerta-persona|crear-alerta-persona|editar-alerta-persona|borrar-alerta-persona', ['only' => ['index', 'show']]);
        $this->middleware('permission:crear-alerta-persona', ['only' => ['create', 'store', 'importarForm', 'importar', 'buscarCoincidencias']]);
        $this->middleware('permission:editar-alerta-persona', ['only' => ['edit', 'update', 'cambiarActivo', 'comentario']]);
        $this->middleware('permission:borrar-alerta-persona', ['only' => ['destroy']]);
    }

    public function index(Request $request): View
    {
        $query = PersonaAlerta::query();

        if ($request->filled('busqueda')) {
            $busqueda = $request->busqueda;
            $query->where(function ($q) use ($busqueda) {
                $q->where('dni', 'like', "%{$busqueda}%")
                    ->orWhere('apellido_nombre', 'like', "%{$busqueda}%")
                    ->orWhere('motivo', 'like', "%{$busqueda}%");
            });
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha_carga', '>=', $request->query('fecha_desde'));
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha_carga', '<=', $request->query('fecha_hasta'));
        }

        $activo = $request->query('activo', '1');

        if ($activo === '1') {
            $query->where('activo', true);
        } elseif ($activo === '0') {
            $query->where('activo', false);
        }

        $personas = $query->orderByDesc('fecha_carga')->orderByDesc('id')->paginate(15)->withQueryString();

        $contadores = [
            'total' => PersonaAlerta::count(),
            'activos' => PersonaAlerta::activos()->count(),
            'inactivos' => PersonaAlerta::inactivos()->count(),
        ];

        $ultimosCargados = PersonaAlerta::activos()->orderByDesc('created_at')->take(5)->get();

        return view('alertas-video.personas.index', compact('personas', 'contadores', 'ultimosCargados'));
    }

    public function create(): View
    {
        return view('alertas-video.personas.crear');
    }

    public function buscarCoincidencias(Request $request): JsonResponse
    {
        $request->validate([
            'nombre' => 'nullable|string|max:150',
            'dni' => 'nullable|string|max:20',
        ]);

        $coincidencias = PersonaAlerta::buscarCoincidencias($request->query('dni'), $request->query('nombre'))
            ->map(fn (PersonaAlerta $persona) => [
                'id' => $persona->id,
                'apellido_nombre' => $persona->apellido_nombre,
                'dni' => $persona->dni,
                'direccion' => $persona->direccion,
                'motivo' => $persona->motivo,
                'activo' => $persona->activo,
                'estado_label' => $persona->estado_label,
                'foto_url' => $persona->foto_url,
                'url_show' => route('alertas-video.personas.show', $persona),
                'url_edit' => route('alertas-video.personas.edit', $persona),
            ])
            ->values();

        return response()->json(['coincidencias' => $coincidencias]);
    }

    public function store(StorePersonaAlertaRequest $request): RedirectResponse
    {
        $datos = $request->validated();
        $datos['identificado'] = $request->boolean('identificado');
        $datos['activo'] = $request->boolean('activo', true);
        unset($datos['foto']);

        $this->service->crear(PersonaAlerta::class, $datos, $request->file('foto'));

        return redirect()->route('alertas-video.personas.index')->with('success', 'Persona cargada correctamente.');
    }

    public function show(PersonaAlerta $personaAlerta): View
    {
        $personaAlerta->load(['movimientos.usuario', 'creadoPor', 'actualizadoPor']);

        return view('alertas-video.personas.show', compact('personaAlerta'));
    }

    public function edit(PersonaAlerta $personaAlerta): View
    {
        return view('alertas-video.personas.editar', compact('personaAlerta'));
    }

    public function update(UpdatePersonaAlertaRequest $request, PersonaAlerta $personaAlerta): RedirectResponse
    {
        $datos = $request->validated();
        $datos['identificado'] = $request->boolean('identificado');
        unset($datos['foto']);

        $nuevoActivo = $request->boolean('activo', true);
        $comentario = $request->input('comentario');

        $this->service->actualizar($personaAlerta, $datos, $request->file('foto'));

        if ($nuevoActivo !== (bool) $personaAlerta->activo) {
            $this->service->cambiarActivo($personaAlerta, $nuevoActivo, $comentario);
        }

        return redirect()->route('alertas-video.personas.show', $personaAlerta)->with('success', 'Persona actualizada correctamente.');
    }

    public function destroy(Request $request, PersonaAlerta $personaAlerta): RedirectResponse
    {
        $request->validate([
            'motivo_eliminacion' => 'required|string|min:10|max:500',
        ], [
            'motivo_eliminacion.required' => 'Debe proporcionar un motivo para la eliminación.',
            'motivo_eliminacion.min' => 'El motivo debe tener al menos 10 caracteres.',
        ]);

        $this->service->eliminar($personaAlerta, $request->motivo_eliminacion);

        return redirect()->route('alertas-video.personas.index')->with('success', 'Persona eliminada correctamente.');
    }

    public function cambiarActivo(Request $request, PersonaAlerta $personaAlerta): RedirectResponse
    {
        $request->validate([
            'activo' => 'required|boolean',
            'comentario' => 'nullable|string|max:500',
        ]);

        $this->service->cambiarActivo($personaAlerta, $request->boolean('activo'), $request->comentario);

        return redirect()->route('alertas-video.personas.show', $personaAlerta)->with('success', 'Estado actualizado correctamente.');
    }

    public function comentario(Request $request, PersonaAlerta $personaAlerta): RedirectResponse
    {
        $request->validate([
            'comentario' => 'required|string|min:3|max:500',
        ], [
            'comentario.required' => 'El comentario es obligatorio.',
        ]);

        $this->service->agregarComentario($personaAlerta, $request->comentario);

        return redirect()->route('alertas-video.personas.show', $personaAlerta)->with('success', 'Nota agregada correctamente.');
    }

    public function importarForm(): View
    {
        return view('alertas-video.personas.importar');
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
            $import = app(PersonaAlertaImport::class);
            Excel::import($import, $request->file('archivo'));

            $mensaje = "Importación completada. {$import->getCreated()} personas creadas, {$import->getOmitidos()} omitidas (ya existían).";

            if (count($import->getErrors()) > 0) {
                $mensaje .= ' Errores: ' . implode(', ', array_slice($import->getErrors(), 0, 5));
            }

            return redirect()->route('alertas-video.personas.index')->with('success', $mensaje);
        } catch (\Exception $e) {
            return redirect()->route('alertas-video.personas.importar')->with('error', 'Error al importar: ' . $e->getMessage());
        }
    }
}
