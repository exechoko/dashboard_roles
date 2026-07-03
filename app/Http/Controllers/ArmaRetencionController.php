<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreArmaRetencionRequest;
use App\Http\Requests\UpdateArmaRetencionRequest;
use App\Models\ArmaMotivo;
use App\Models\ArmaRetencion;
use App\Models\Personal;
use App\Exports\ArmaRetencionesExport;
use App\Imports\ArmaRetencionImport;
use App\Services\ArmaRetencionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class ArmaRetencionController extends Controller
{
    public function __construct(private ArmaRetencionService $service)
    {
        $this->middleware('permission:ver-arma-retencion|crear-arma-retencion|editar-arma-retencion|borrar-arma-retencion', ['only' => ['index']]);
        $this->middleware('permission:crear-arma-retencion', ['only' => ['create', 'store']]);
        $this->middleware('permission:editar-arma-retencion', ['only' => ['edit', 'update', 'elevar', 'devolver']]);
        $this->middleware('permission:borrar-arma-retencion', ['only' => ['destroy']]);
    }

    public function index(Request $request): View
    {
        $query = ArmaRetencion::query()
            ->with(['personal', 'motivo', 'creadoPor']);

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->filled('motivo_id')) {
            $query->where('motivo_id', $request->motivo_id);
        }

        if ($request->filled('busqueda')) {
            $busqueda = $request->busqueda;
            $query->where(function ($q) use ($busqueda) {
                $q->whereHas('personal', function ($q2) use ($busqueda) {
                    $q2->where('nombre', 'like', "%{$busqueda}%")
                       ->orWhere('apellido', 'like', "%{$busqueda}%")
                       ->orWhere('lp', 'like', "%{$busqueda}%");
                })->orWhere('numeracion_arma', 'like', "%{$busqueda}%");
            });
        }

        $retenciones = $query->orderByDesc('fecha_posesion')
            ->paginate(15);

        return view('arma-retenciones.index', compact('retenciones'));
    }

    public function create(): View
    {
        $personales = Personal::orderBy('apellido')->orderBy('nombre')->get();
        $motivos = ArmaMotivo::activos()->orderBy('nombre')->get();

        return view('arma-retenciones.crear', compact('personales', 'motivos'));
    }

    public function store(StoreArmaRetencionRequest $request): RedirectResponse
    {
        $this->service->crear($request->validated());

        return redirect()->route('armas.retenciones.index')->with('success', 'Retención de arma registrada correctamente.');
    }

    public function show(ArmaRetencion $armaRetencion): View
    {
        $armaRetencion->load(['personal', 'motivo', 'creadoPor', 'actualizadoPor']);

        return view('arma-retenciones.show', compact('armaRetencion'));
    }

    public function edit(ArmaRetencion $armaRetencion): View
    {
        $personales = Personal::orderBy('apellido')->orderBy('nombre')->get();
        $motivos = ArmaMotivo::activos()->orderBy('nombre')->get();

        return view('arma-retenciones.editar', compact('armaRetencion', 'personales', 'motivos'));
    }

    public function update(UpdateArmaRetencionRequest $request, ArmaRetencion $armaRetencion): RedirectResponse
    {
        $this->service->actualizar($armaRetencion, $request->validated());

        return redirect()->route('armas.retenciones.index')->with('success', 'Retención de arma actualizada correctamente.');
    }

    public function destroy(ArmaRetencion $armaRetencion): RedirectResponse
    {
        $this->service->eliminar($armaRetencion);

        return redirect()->route('armas.retenciones.index')->with('success', 'Retención de arma eliminada correctamente.');
    }

    public function elevar(Request $request, ArmaRetencion $armaRetencion): RedirectResponse
    {
        $request->validate([
            'fecha_elevacion' => 'nullable|date',
        ], [
            'fecha_elevacion.date' => 'La fecha de elevación debe ser una fecha válida.',
        ]);

        $this->service->elevar($armaRetencion, $request->fecha_elevacion);

        return redirect()->route('armas.retenciones.show', $armaRetencion)->with('success', 'Arma elevada a Jefatura Central correctamente.');
    }

    public function devolver(Request $request, ArmaRetencion $armaRetencion): RedirectResponse
    {
        $request->validate([
            'fecha_devolucion' => 'nullable|date',
        ], [
            'fecha_devolucion.date' => 'La fecha de devolución debe ser una fecha válida.',
        ]);

        $this->service->devolver($armaRetencion, $request->fecha_devolucion);

        return redirect()->route('armas.retenciones.show', $armaRetencion)->with('success', 'Arma devuelta al funcionario correctamente.');
    }

    public function importarForm(): View
    {
        return view('arma-retenciones.importar');
    }

    public function importar(Request $request): RedirectResponse
    {
        $request->validate([
            'archivo' => 'required|file|mimes:xlsx,xls',
        ], [
            'archivo.required' => 'Debe seleccionar un archivo',
            'archivo.file' => 'El archivo debe ser válido',
            'archivo.mimes' => 'El archivo debe ser de tipo Excel (.xlsx o .xls)',
        ]);

        try {
            $import = new ArmaRetencionImport();
            Excel::import($import, $request->file('archivo'));

            $mensaje = "Importación completada. {$import->getCreated()} registros creados.";

            if (count($import->getErrors()) > 0) {
                $mensaje .= " Errores: " . implode(', ', array_slice($import->getErrors(), 0, 5));
                if (count($import->getErrors()) > 5) {
                    $mensaje .= '... y ' . (count($import->getErrors()) - 5) . ' más';
                }
            }

            return redirect()->route('armas.retenciones.index')->with('success', $mensaje);
        } catch (\Exception $e) {
            return redirect()->route('armas.retenciones.importar')->with('error', 'Error al importar: ' . $e->getMessage());
        }
    }

    public function exportar(Request $request)
    {
        $filters = [
            'estado' => $request->input('estado'),
            'tipo' => $request->input('tipo'),
            'motivo_id' => $request->input('motivo_id'),
            'busqueda' => $request->input('busqueda'),
        ];

        $filename = 'retenciones_armas_' . now()->format('Y-m-d_His') . '.xlsx';

        return Excel::download(new ArmaRetencionesExport($filters), $filename);
    }
}
