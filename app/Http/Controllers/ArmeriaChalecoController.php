<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreArmeriaAdjuntoRequest;
use App\Http\Requests\StoreArmeriaChalecoRequest;
use App\Http\Requests\UpdateArmeriaChalecoRequest;
use App\Imports\ArmeriaChalecoImport;
use App\Models\ArmeriaAdjunto;
use App\Models\ArmeriaChaleco;
use App\Services\ArmeriaMovimientoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class ArmeriaChalecoController extends Controller
{
    public function __construct(private ArmeriaMovimientoService $service)
    {
        $this->middleware('permission:ver-armeria|crear-armeria|editar-armeria|borrar-armeria', ['only' => ['index', 'show']]);
        $this->middleware('permission:crear-armeria', ['only' => ['create', 'store', 'importarForm', 'importar']]);
        $this->middleware('permission:editar-armeria', ['only' => [
            'edit', 'update', 'cambiarEstado', 'enviarJefatura', 'retornarDivision', 'comentario', 'adjuntar',
        ]]);
        $this->middleware('permission:borrar-armeria', ['only' => ['destroy', 'destroyAdjunto']]);
    }

    public function index(Request $request): View
    {
        $query = ArmeriaChaleco::query();

        if ($request->filled('busqueda')) {
            $busqueda = $request->busqueda;
            $query->where(function ($q) use ($busqueda) {
                $q->where('numero_serie', 'like', "%{$busqueda}%")
                    ->orWhere('marca', 'like', "%{$busqueda}%")
                    ->orWhere('modelo', 'like', "%{$busqueda}%")
                    ->orWhere('movil', 'like', "%{$busqueda}%");
            });
        }

        if ($request->filled('talle')) {
            $query->where('talle', $request->talle);
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('ubicacion')) {
            $query->where('ubicacion', $request->ubicacion);
        }

        $chalecos = $query->orderBy('numero_serie')->paginate(15)->withQueryString();

        $contadores = [
            'total' => ArmeriaChaleco::count(),
            'en_division' => ArmeriaChaleco::enDivision()->count(),
            'en_jefatura' => ArmeriaChaleco::enJefaturaCentral()->count(),
            'en_reparacion' => ArmeriaChaleco::where('estado', 'EN_REPARACION')->count(),
        ];

        return view('arma-armeria.chalecos.index', compact('chalecos', 'contadores'));
    }

    public function create(): View
    {
        return view('arma-armeria.chalecos.crear');
    }

    public function store(StoreArmeriaChalecoRequest $request): RedirectResponse
    {
        $this->service->crear(ArmeriaChaleco::class, $request->validated());

        return redirect()->route('armas.armeria.chalecos.index')->with('success', 'Chaleco cargado correctamente.');
    }

    public function show(ArmeriaChaleco $armeriaChaleco): View
    {
        $armeriaChaleco->load(['movimientos.usuario', 'adjuntos.usuario', 'creadoPor', 'actualizadoPor']);

        return view('arma-armeria.chalecos.show', compact('armeriaChaleco'));
    }

    public function edit(ArmeriaChaleco $armeriaChaleco): View
    {
        return view('arma-armeria.chalecos.editar', compact('armeriaChaleco'));
    }

    public function update(UpdateArmeriaChalecoRequest $request, ArmeriaChaleco $armeriaChaleco): RedirectResponse
    {
        $this->service->actualizar($armeriaChaleco, $request->validated());

        return redirect()->route('armas.armeria.chalecos.show', $armeriaChaleco)->with('success', 'Chaleco actualizado correctamente.');
    }

    public function destroy(Request $request, ArmeriaChaleco $armeriaChaleco): RedirectResponse
    {
        $request->validate([
            'motivo_eliminacion' => 'required|string|min:10|max:500',
        ], [
            'motivo_eliminacion.required' => 'Debe proporcionar un motivo para la eliminación.',
            'motivo_eliminacion.min' => 'El motivo debe tener al menos 10 caracteres.',
        ]);

        $this->service->eliminar($armeriaChaleco, $request->motivo_eliminacion);

        return redirect()->route('armas.armeria.chalecos.index')->with('success', 'Chaleco eliminado correctamente.');
    }

    public function cambiarEstado(Request $request, ArmeriaChaleco $armeriaChaleco): RedirectResponse
    {
        $request->validate([
            'estado' => 'required|string|in:' . implode(',', ArmeriaChaleco::ESTADOS),
            'comentario' => 'nullable|string|max:500',
            'fecha' => 'nullable|date|before_or_equal:now',
        ], [
            'fecha.before_or_equal' => 'La fecha del movimiento no puede ser futura.',
        ]);

        $this->service->cambiarEstado($armeriaChaleco, $request->estado, $request->comentario, $request->fecha);

        return redirect()->route('armas.armeria.chalecos.show', $armeriaChaleco)->with('success', 'Estado actualizado correctamente.');
    }

    public function enviarJefatura(Request $request, ArmeriaChaleco $armeriaChaleco): RedirectResponse
    {
        $request->validate([
            'comentario' => 'nullable|string|max:500',
            'fecha' => 'nullable|date|before_or_equal:now',
        ], [
            'fecha.before_or_equal' => 'La fecha del movimiento no puede ser futura.',
        ]);

        $this->service->enviarAJefaturaCentral($armeriaChaleco, $request->comentario, $request->fecha);

        return redirect()->route('armas.armeria.chalecos.show', $armeriaChaleco)->with('success', 'Chaleco enviado a Armería Jefatura Central.');
    }

    public function retornarDivision(Request $request, ArmeriaChaleco $armeriaChaleco): RedirectResponse
    {
        $request->validate([
            'comentario' => 'nullable|string|max:500',
            'fecha' => 'nullable|date|before_or_equal:now',
        ], [
            'fecha.before_or_equal' => 'La fecha del movimiento no puede ser futura.',
        ]);

        $this->service->retornarADivision911($armeriaChaleco, $request->comentario, $request->fecha);

        return redirect()->route('armas.armeria.chalecos.show', $armeriaChaleco)->with('success', 'Chaleco retornado a Armería División 911.');
    }

    public function comentario(Request $request, ArmeriaChaleco $armeriaChaleco): RedirectResponse
    {
        $request->validate([
            'comentario' => 'required|string|min:3|max:500',
            'fecha' => 'nullable|date|before_or_equal:now',
        ], [
            'comentario.required' => 'El comentario es obligatorio.',
            'fecha.before_or_equal' => 'La fecha del movimiento no puede ser futura.',
        ]);

        $this->service->agregarComentario($armeriaChaleco, $request->comentario, $request->fecha);

        return redirect()->route('armas.armeria.chalecos.show', $armeriaChaleco)->with('success', 'Comentario agregado correctamente.');
    }

    public function adjuntar(StoreArmeriaAdjuntoRequest $request, ArmeriaChaleco $armeriaChaleco): RedirectResponse
    {
        $this->service->adjuntar($armeriaChaleco, $request->file('archivo'));

        return redirect()->route('armas.armeria.chalecos.show', $armeriaChaleco)->with('success', 'Archivo adjuntado correctamente.');
    }

    public function destroyAdjunto(ArmeriaChaleco $armeriaChaleco, ArmeriaAdjunto $adjunto): RedirectResponse
    {
        $this->service->eliminarAdjunto($adjunto);

        return redirect()->route('armas.armeria.chalecos.show', $armeriaChaleco)->with('success', 'Adjunto eliminado correctamente.');
    }

    public function importarForm(): View
    {
        return view('arma-armeria.chalecos.importar');
    }

    public function importar(Request $request): RedirectResponse
    {
        $request->validate([
            'archivo' => 'required|file|mimes:xlsx,xls',
        ], [
            'archivo.required' => 'Debe seleccionar un archivo',
            'archivo.mimes' => 'El archivo debe ser de tipo Excel (.xlsx o .xls)',
        ]);

        try {
            $import = app(ArmeriaChalecoImport::class);
            Excel::import($import, $request->file('archivo'));

            $mensaje = "Importación completada. {$import->getCreated()} chalecos creados, {$import->getOmitidos()} omitidos (ya existían).";

            if ($import->getOmitidosEliminados() > 0) {
                $mensaje .= " {$import->getOmitidosEliminados()} omitidos por corresponder a registros eliminados previamente.";
            }

            if (count($import->getErrors()) > 0) {
                $mensaje .= ' Errores: ' . implode(', ', array_slice($import->getErrors(), 0, 5));
            }

            return redirect()->route('armas.armeria.chalecos.index')->with('success', $mensaje);
        } catch (\Exception $e) {
            return redirect()->route('armas.armeria.chalecos.importar')->with('error', 'Error al importar: ' . $e->getMessage());
        }
    }
}
