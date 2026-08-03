<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreArmeriaAdjuntoRequest;
use App\Http\Requests\StoreArmeriaArmaRequest;
use App\Http\Requests\UpdateArmeriaArmaRequest;
use App\Imports\ArmeriaArmaImport;
use App\Models\ArmeriaAdjunto;
use App\Models\ArmeriaArma;
use App\Services\ArmeriaMovimientoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class ArmeriaArmaController extends Controller
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
        $query = ArmeriaArma::query();

        if ($request->filled('busqueda')) {
            $busqueda = $request->busqueda;
            $query->where(function ($q) use ($busqueda) {
                $q->where('numero_serie', 'like', "%{$busqueda}%")
                    ->orWhere('marca', 'like', "%{$busqueda}%")
                    ->orWhere('modelo', 'like', "%{$busqueda}%");
            });
        }

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('ubicacion')) {
            $query->where('ubicacion', $request->ubicacion);
        }

        $armas = $query->orderBy('numero_serie')->paginate(15)->withQueryString();

        $contadores = [
            'total' => ArmeriaArma::count(),
            'en_division' => ArmeriaArma::enDivision()->count(),
            'en_jefatura' => ArmeriaArma::enJefaturaCentral()->count(),
            'en_reparacion' => ArmeriaArma::where('estado', 'EN_REPARACION')->count(),
        ];

        return view('arma-armeria.armas.index', compact('armas', 'contadores'));
    }

    public function create(): View
    {
        return view('arma-armeria.armas.crear');
    }

    public function store(StoreArmeriaArmaRequest $request): RedirectResponse
    {
        $this->service->crear(ArmeriaArma::class, $request->validated());

        return redirect()->route('armas.armeria.armas.index')->with('success', 'Arma secundaria cargada correctamente.');
    }

    public function show(ArmeriaArma $armeriaArma): View
    {
        $armeriaArma->load(['movimientos.usuario', 'adjuntos.usuario', 'creadoPor', 'actualizadoPor']);

        return view('arma-armeria.armas.show', compact('armeriaArma'));
    }

    public function edit(ArmeriaArma $armeriaArma): View
    {
        return view('arma-armeria.armas.editar', compact('armeriaArma'));
    }

    public function update(UpdateArmeriaArmaRequest $request, ArmeriaArma $armeriaArma): RedirectResponse
    {
        $this->service->actualizar($armeriaArma, $request->validated());

        return redirect()->route('armas.armeria.armas.show', $armeriaArma)->with('success', 'Arma secundaria actualizada correctamente.');
    }

    public function destroy(Request $request, ArmeriaArma $armeriaArma): RedirectResponse
    {
        $request->validate([
            'motivo_eliminacion' => 'required|string|min:10|max:500',
        ], [
            'motivo_eliminacion.required' => 'Debe proporcionar un motivo para la eliminación.',
            'motivo_eliminacion.min' => 'El motivo debe tener al menos 10 caracteres.',
        ]);

        $this->service->eliminar($armeriaArma, $request->motivo_eliminacion);

        return redirect()->route('armas.armeria.armas.index')->with('success', 'Arma secundaria eliminada correctamente.');
    }

    public function cambiarEstado(Request $request, ArmeriaArma $armeriaArma): RedirectResponse
    {
        $request->validate([
            'estado' => 'required|string|in:' . implode(',', ArmeriaArma::ESTADOS),
            'comentario' => 'nullable|string|max:500',
            'fecha' => 'nullable|date|before_or_equal:now',
        ], [
            'fecha.before_or_equal' => 'La fecha del movimiento no puede ser futura.',
        ]);

        $this->service->cambiarEstado($armeriaArma, $request->estado, $request->comentario, $request->fecha);

        return redirect()->route('armas.armeria.armas.show', $armeriaArma)->with('success', 'Estado actualizado correctamente.');
    }

    public function enviarJefatura(Request $request, ArmeriaArma $armeriaArma): RedirectResponse
    {
        $request->validate([
            'comentario' => 'nullable|string|max:500',
            'fecha' => 'nullable|date|before_or_equal:now',
        ], [
            'fecha.before_or_equal' => 'La fecha del movimiento no puede ser futura.',
        ]);

        $this->service->enviarAJefaturaCentral($armeriaArma, $request->comentario, $request->fecha);

        return redirect()->route('armas.armeria.armas.show', $armeriaArma)->with('success', 'Arma enviada a Armería Jefatura Central.');
    }

    public function retornarDivision(Request $request, ArmeriaArma $armeriaArma): RedirectResponse
    {
        $request->validate([
            'comentario' => 'nullable|string|max:500',
            'fecha' => 'nullable|date|before_or_equal:now',
        ], [
            'fecha.before_or_equal' => 'La fecha del movimiento no puede ser futura.',
        ]);

        $this->service->retornarADivision911($armeriaArma, $request->comentario, $request->fecha);

        return redirect()->route('armas.armeria.armas.show', $armeriaArma)->with('success', 'Arma retornada a Armería División 911.');
    }

    public function comentario(Request $request, ArmeriaArma $armeriaArma): RedirectResponse
    {
        $request->validate([
            'comentario' => 'required|string|min:3|max:500',
            'fecha' => 'nullable|date|before_or_equal:now',
        ], [
            'comentario.required' => 'El comentario es obligatorio.',
            'fecha.before_or_equal' => 'La fecha del movimiento no puede ser futura.',
        ]);

        $this->service->agregarComentario($armeriaArma, $request->comentario, $request->fecha);

        return redirect()->route('armas.armeria.armas.show', $armeriaArma)->with('success', 'Comentario agregado correctamente.');
    }

    public function adjuntar(StoreArmeriaAdjuntoRequest $request, ArmeriaArma $armeriaArma): RedirectResponse
    {
        $this->service->adjuntar($armeriaArma, $request->file('archivo'));

        return redirect()->route('armas.armeria.armas.show', $armeriaArma)->with('success', 'Archivo adjuntado correctamente.');
    }

    public function destroyAdjunto(ArmeriaArma $armeriaArma, ArmeriaAdjunto $adjunto): RedirectResponse
    {
        $this->service->eliminarAdjunto($adjunto);

        return redirect()->route('armas.armeria.armas.show', $armeriaArma)->with('success', 'Adjunto eliminado correctamente.');
    }

    public function importarForm(): View
    {
        return view('arma-armeria.armas.importar');
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
            $import = app(ArmeriaArmaImport::class);
            Excel::import($import, $request->file('archivo'));

            $mensaje = "Importación completada. {$import->getCreated()} armas creadas, {$import->getOmitidos()} omitidas (ya existían).";

            if ($import->getOmitidosEliminados() > 0) {
                $mensaje .= " {$import->getOmitidosEliminados()} omitidas por corresponder a registros eliminados previamente.";
            }

            if (count($import->getErrors()) > 0) {
                $mensaje .= ' Errores: ' . implode(', ', array_slice($import->getErrors(), 0, 5));
            }

            return redirect()->route('armas.armeria.armas.index')->with('success', $mensaje);
        } catch (\Exception $e) {
            return redirect()->route('armas.armeria.armas.importar')->with('error', 'Error al importar: ' . $e->getMessage());
        }
    }
}
