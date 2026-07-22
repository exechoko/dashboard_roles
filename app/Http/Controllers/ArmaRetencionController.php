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
use Carbon\Carbon;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpWord\TemplateProcessor;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ArmaRetencionController extends Controller
{
    public function __construct(private ArmaRetencionService $service)
    {
        $this->middleware('permission:ver-arma-retencion|crear-arma-retencion|editar-arma-retencion|borrar-arma-retencion', ['only' => ['index']]);
        $this->middleware('permission:crear-arma-retencion', ['only' => ['create', 'store', 'generarDocumento']]);
        $this->middleware('permission:editar-arma-retencion', ['only' => ['edit', 'update', 'elevar', 'devolver']]);
        $this->middleware('permission:borrar-arma-retencion', ['only' => ['destroy']]);
    }

    public function index(Request $request): View
    {
        $alertas_vencimiento = ArmaRetencion::where('estado', 'EN_ARMERIA')
            ->whereNotNull('dias_restantes')
            ->where('dias_restantes', '<=', 15)
            ->with(['personal', 'motivo', 'arma.tipo', 'chaleco'])
            ->orderBy('dias_restantes')
            ->get();

        $query = ArmaRetencion::query()
            ->with(['personal', 'motivo', 'creadoPor', 'arma.tipo', 'chaleco'])
            ->activas();

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
                       ->orWhere('lp', 'like', "%{$busqueda}%")
                       ->orWhere('numeracion_arma', 'like', "%{$busqueda}%");
                })->orWhere('arma_numero', 'like', "%{$busqueda}%");
            });
        }

        $retenciones = $query->orderByDesc('fecha_posesion')
            ->paginate(15);

        $ultimasDevoluciones = ArmaRetencion::query()
            ->with(['personal', 'motivo', 'arma.tipo', 'chaleco'])
            ->devueltas()
            ->orderByDesc('fecha_devolucion')
            ->limit(10)
            ->get();

        return view('arma-retenciones.index', compact('retenciones', 'alertas_vencimiento', 'ultimasDevoluciones'));
    }

    public function create(): View
    {
        $personales = Personal::whereDoesntHave('retenciones', function ($query) {
            $query->whereIn('estado', ['EN_ARMERIA', 'EN_JEF_CENTRAL']);
        })->whereHas('armaAsignacionActual')
            ->with(['armaAsignacionActual.arma.tipo', 'chalecoAsignacionActual.chaleco'])
            ->orderBy('apellido')->orderBy('nombre')->get();
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
        $armaRetencion->load(['personal.tipoArma', 'arma.tipo', 'chaleco', 'motivo', 'creadoPor', 'actualizadoPor', 'historial.usuario']);

        return view('arma-retenciones.show', compact('armaRetencion'));
    }

    public function edit(ArmaRetencion $armaRetencion): View
    {
        $personales = Personal::with(['armaAsignacionActual.arma.tipo', 'chalecoAsignacionActual.chaleco'])
            ->where(function ($query) use ($armaRetencion) {
                $query->whereHas('armaAsignacionActual')
                    ->orWhere('id', $armaRetencion->personal_id);
            })
            ->orderBy('apellido')->orderBy('nombre')->get();
        $motivos = ArmaMotivo::activos()->orderBy('nombre')->get();

        return view('arma-retenciones.editar', compact('armaRetencion', 'personales', 'motivos'));
    }

    public function update(UpdateArmaRetencionRequest $request, ArmaRetencion $armaRetencion): RedirectResponse
    {
        $this->service->actualizar($armaRetencion, $request->validated());

        return redirect()->route('armas.retenciones.index')->with('success', 'Retención de arma actualizada correctamente.');
    }

    public function destroy(Request $request, ArmaRetencion $armaRetencion): RedirectResponse
    {
        $request->validate([
            'motivo_eliminacion' => 'required|string|min:10|max:500',
        ], [
            'motivo_eliminacion.required' => 'Debe proporcionar un motivo para la eliminación.',
            'motivo_eliminacion.min' => 'El motivo debe tener al menos 10 caracteres.',
            'motivo_eliminacion.max' => 'El motivo no debe superar los 500 caracteres.',
        ]);

        $this->service->eliminar($armaRetencion, $request->motivo_eliminacion);

        return redirect()->route('armas.retenciones.index')->with('success', 'Retención de arma eliminada correctamente.');
    }

    public function elevar(Request $request, ArmaRetencion $armaRetencion): RedirectResponse
    {
        $request->validate([
            'fecha_elevacion' => 'nullable|date',
            'comentario' => 'nullable|string|max:500',
        ], [
            'fecha_elevacion.date' => 'La fecha de elevación debe ser una fecha válida.',
        ]);

        $this->service->elevar($armaRetencion, $request->fecha_elevacion, $request->comentario);

        return redirect()->route('armas.retenciones.show', $armaRetencion)->with('success', 'Arma elevada a Jefatura Central correctamente.');
    }

    public function devolver(Request $request, ArmaRetencion $armaRetencion): RedirectResponse
    {
        $request->validate([
            'fecha_devolucion' => 'nullable|date',
            'comentario' => 'nullable|string|max:500',
        ], [
            'fecha_devolucion.date' => 'La fecha de devolución debe ser una fecha válida.',
        ]);

        $this->service->devolver($armaRetencion, $request->fecha_devolucion, $request->comentario);

        return redirect()->route('armas.retenciones.show', $armaRetencion)->with('success', 'Arma devuelta al funcionario correctamente.');
    }

    public function historial(Request $request): View
    {
        $query = ArmaRetencion::query()
            ->with(['personal', 'motivo', 'arma.tipo', 'chaleco'])
            ->devueltas();

        if ($request->filled('busqueda')) {
            $busqueda = $request->busqueda;
            $query->where(function ($q) use ($busqueda) {
                $q->whereHas('personal', function ($q2) use ($busqueda) {
                    $q2->where('nombre', 'like', "%{$busqueda}%")
                       ->orWhere('apellido', 'like', "%{$busqueda}%")
                       ->orWhere('lp', 'like', "%{$busqueda}%");
                })->orWhere('arma_numero', 'like', "%{$busqueda}%");
            });
        }

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        $devoluciones = $query->orderByDesc('fecha_devolucion')->paginate(15);

        return view('arma-retenciones.historial', compact('devoluciones'));
    }

    public function agregarComentario(Request $request, ArmaRetencion $armaRetencion): RedirectResponse
    {
        $request->validate([
            'comentario' => 'required|string|min:3|max:500',
        ], [
            'comentario.required' => 'El comentario es obligatorio.',
            'comentario.min' => 'El comentario debe tener al menos 3 caracteres.',
            'comentario.max' => 'El comentario no debe superar los 500 caracteres.',
        ]);

        $this->service->agregarComentario($armaRetencion, $request->comentario);

        return redirect()->route('armas.retenciones.show', $armaRetencion)->with('success', 'Comentario agregado correctamente.');
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
            $import = app(ArmaRetencionImport::class);
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

    public function generarDocumento(ArmaRetencion $armaRetencion): BinaryFileResponse|RedirectResponse
    {
        $templatePath = storage_path('app/templates/template_acta_retencion_arma_reglamentaria.docx');

        if (!file_exists($templatePath)) {
            return redirect()->back()->with('error', 'Template del acta de retención no encontrado.');
        }

        try {
            $armaRetencion->load(['personal', 'arma', 'motivo']);
            $personal = $armaRetencion->personal;

            $hora = $armaRetencion->hora_posesion
                ? Carbon::parse($armaRetencion->hora_posesion)->format('H:i')
                : now()->format('H:i');

            $templateProcessor = new TemplateProcessor($templatePath);
            $templateProcessor->setValue('CIUDAD', $armaRetencion->ciudad ?? '');
            $templateProcessor->setValue('DEPARTAMENTO', ArmaRetencion::DEPARTAMENTO);
            $templateProcessor->setValue('PROVINCIA', ArmaRetencion::PROVINCIA);
            $templateProcessor->setValue('DIA', $armaRetencion->fecha_posesion->format('d'));
            $templateProcessor->setValue('MES', $this->mesEnEspanol($armaRetencion->fecha_posesion));
            $templateProcessor->setValue('ANIO', $armaRetencion->fecha_posesion->format('Y'));
            $templateProcessor->setValue('HORA', $hora);
            $templateProcessor->setValue('MARCA_MODELO', $armaRetencion->marca_modelo ?? '');
            $templateProcessor->setValue('NRO_ARMA', $armaRetencion->arma_numero ?? $armaRetencion->arma?->numero ?? '');
            $templateProcessor->setValue('ESTADO_CONSERVACION', $armaRetencion->estado_conservacion ?? '');
            $templateProcessor->setValue('CON_CARGADOR', $armaRetencion->con_cargador ? 'CON' : 'SIN');
            $templateProcessor->setValue('CON_CARTUCHERIA', $armaRetencion->con_cartucheria ? 'CON' : 'SIN');
            $templateProcessor->setValue('JERARQUIA', $personal->jerarquia);
            $templateProcessor->setValue('APELLIDO_NOMBRE', $personal->apellido . ', ' . $personal->nombre);
            $templateProcessor->setValue('LP', $personal->lp);
            $templateProcessor->setValue('DNI', $personal->dni ?? '');
            $templateProcessor->setValue('MOTIVO_RETENCION', $armaRetencion->motivo->textoParaActa());

            $nombreFuncionario = Str::slug($personal->apellido . ' ' . $personal->nombre);
            $fileName = 'acta_retencion_' . $nombreFuncionario . '_' . now()->format('Ymd_His') . '.docx';
            $relativePath = 'arma_retenciones/actas/' . $fileName;
            $destinationPath = storage_path('app/' . $relativePath);

            if (!file_exists(dirname($destinationPath))) {
                mkdir(dirname($destinationPath), 0755, true);
            }

            $templateProcessor->saveAs($destinationPath);

            return response()->download($destinationPath, $fileName, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ]);
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Error al generar el acta: ' . $e->getMessage());
        }
    }

    private function mesEnEspanol(Carbon $fecha): string
    {
        $meses = [
            1 => 'Enero',
            2 => 'Febrero',
            3 => 'Marzo',
            4 => 'Abril',
            5 => 'Mayo',
            6 => 'Junio',
            7 => 'Julio',
            8 => 'Agosto',
            9 => 'Septiembre',
            10 => 'Octubre',
            11 => 'Noviembre',
            12 => 'Diciembre',
        ];

        return $meses[(int) $fecha->format('n')];
    }
}
