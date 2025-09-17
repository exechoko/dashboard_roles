<?php

namespace App\Http\Controllers;

use App\Models\Destino;
use App\Models\DetalleDevolucionBodycam;
use App\Models\DetalleEntregaBodycam;
use App\Models\DevolucionBodycam;
use App\Models\EntregaBodycam;
use App\Models\Bodycam;
use Carbon\Carbon;
use DB;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Log;
use PhpOffice\PhpWord\TemplateProcessor;
use PhpOffice\PhpWord\IOFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EntregasBodycamsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $query = EntregaBodycam::with(['bodycams', 'detalleEntregas']);

        // Aplicar filtros de búsqueda
        if ($request->filled('codigo')) {
            $query->buscarPorCodigo($request->codigo);
        }

        if ($request->filled('numero_serie')) {
            $query->buscarPorSerie($request->numero_serie);
        }

        if ($request->filled('fecha')) {
            $query->buscarPorFecha($request->fecha);
        }

        if ($request->filled('dependencia')) {
            $query->buscarPorDependencia($request->dependencia);
        }

        $entregas = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('entregas.entregas-bodycams.index', compact('entregas'));
    }

    public function create()
    {
        // Obtener bodycams disponibles (no entregadas actualmente)
        $bodycamsDisponibles = Bodycam::where('estado', 'disponible')
            ->orderBy('codigo', 'asc')
            ->get();

        // Destinos
        $destinos = Destino::all();

        return view('entregas.entregas-bodycams.crear', compact('bodycamsDisponibles', 'destinos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'fecha_entrega' => 'required|date',
            'hora_entrega' => 'required',
            'dependencia' => 'required|string|max:255',
            'motivo_operativo' => 'required|string',
            'bodycams_seleccionadas' => 'required|array|min:1',
            'bodycams_seleccionadas.*' => 'exists:bodycams,id',
            'imagen1' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'imagen2' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'imagen3' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'archivo' => 'nullable|mimes:pdf,doc,docx,xlsx,zip,rar|max:2048'
        ]);

        // Procesar imágenes y archivos
        $rutasImagenes = [];
        for ($i = 1; $i <= 3; $i++) {
            $inputName = 'imagen' . $i;
            if ($request->hasFile($inputName)) {
                $rutaImagen = $request->file($inputName)->store('', 'anexos');
                $rutasImagenes[] = 'anexos/' . $rutaImagen;
                Log::info("Nueva imagen {$i} subida: anexos/{$rutaImagen}");
            }
        }

        // Manejo del archivo adjunto
        if ($request->hasFile('archivo')) {
            $rutaArchivo = $request->file('archivo')->store('', 'anexos');
            $rutasImagenes[] = 'anexos/' . $rutaArchivo;
            Log::info("Nuevo archivo adjunto subido: anexos/{$rutaArchivo}");
        }

        try {
            DB::beginTransaction();

            // Crear la entrega principal
            $entrega = EntregaBodycam::create([
                'fecha_entrega' => $request->fecha_entrega,
                'hora_entrega' => $request->hora_entrega,
                'dependencia' => $request->dependencia,
                'personal_receptor' => $request->personal_receptor ?? '',
                'legajo_receptor' => $request->legajo_receptor ?? '',
                'personal_entrega' => $request->personal_entrega ?? '',
                'legajo_entrega' => $request->legajo_entrega ?? '',
                'motivo_operativo' => $request->motivo_operativo,
                'observaciones' => $request->observaciones,
                'rutas_imagenes' => json_encode($rutasImagenes),
                'usuario_creador' => auth()->user()->name
            ]);

            // Crear el detalle de entregas
            foreach ($request->bodycams_seleccionadas as $bodycamId) {
                DetalleEntregaBodycam::create([
                    'entrega_id' => $entrega->id,
                    'bodycam_id' => $bodycamId
                ]);

                // Actualizar el estado de la bodycam a 'entregada'
                Bodycam::find($bodycamId)->update(['estado' => 'entregada']);
            }

            DB::commit();

            return redirect()->route('entrega-bodycams.show', $entrega->id)
                ->with('success', 'Acta de entrega de bodycams creada exitosamente');

        } catch (Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->with('error', 'Error al crear el acta de entrega: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function show($id)
    {
        $entrega = EntregaBodycam::with([
            'bodycams',
            'detalleEntregas.bodycam',
            'devoluciones'
        ])->findOrFail($id);

        return view('entregas.entregas-bodycams.show', compact('entrega'));
    }

    public function edit($id)
    {
        $entrega = EntregaBodycam::with('bodycams')->findOrFail($id);

        // Obtener bodycams disponibles o que ya están asignadas a esta entrega
        $bodycamsDisponibles = Bodycam::where(function ($query) use ($entrega) {
            $query->where('estado', 'disponible');
        })
        ->orWhere(function ($query) use ($entrega) {
            $query->whereIn('id', $entrega->bodycams->pluck('id'));
        })
        ->get();

        // Destinos
        $destinos = Destino::all();

        return view(
            'entregas.entregas-bodycams.editar',
            compact(
                'entrega',
                'bodycamsDisponibles',
                'destinos'
            )
        );
    }

    public function update(Request $request, $id)
    {
        $entrega = EntregaBodycam::findOrFail($id);

        $request->validate([
            'fecha_entrega' => 'required|date',
            'hora_entrega' => 'required',
            'dependencia' => 'required|string|max:255',
            'personal_receptor' => 'required|string|max:255',
            'legajo_receptor' => 'nullable|string|max:50',
            'personal_entrega' => 'required|string|max:255',
            'legajo_entrega' => 'nullable|string|max:50',
            'motivo_operativo' => 'required|string',
            'bodycams_seleccionadas' => 'required|array|min:1',
            'bodycams_seleccionadas.*' => 'exists:bodycams,id',
            'imagen1' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'imagen2' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'imagen3' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'archivo' => 'nullable|mimes:pdf,doc,docx,xlsx,zip,rar|max:2048'
        ]);

        // Obtener las rutas de imágenes existentes
        $rutasImagenesExistentes = json_decode($entrega->rutas_imagenes, true) ?? [];
        $rutasImagenes = $rutasImagenesExistentes;

        // Procesar las nuevas imágenes
        $hayNuevasImagenes = false;
        for ($i = 1; $i <= 3; $i++) {
            $inputName = 'imagen' . $i;
            if ($request->hasFile($inputName)) {
                $rutaImagen = $request->file($inputName)->store('', 'anexos');
                $rutasImagenes[] = 'anexos/' . $rutaImagen;
                $hayNuevasImagenes = true;
                Log::info("Nueva imagen {$i} subida: anexos/{$rutaImagen}");
            }
        }

        // Manejo del archivo adjunto
        if ($request->hasFile('archivo')) {
            $rutaArchivo = $request->file('archivo')->store('', 'anexos');
            $rutasImagenes[] = 'anexos/' . $rutaArchivo;
            Log::info("Nuevo archivo adjunto subido: anexos/{$rutaArchivo}");
        }

        try {
            DB::beginTransaction();

            // Actualizar la entrega principal
            $entrega->update([
                'fecha_entrega' => $request->fecha_entrega,
                'hora_entrega' => $request->hora_entrega,
                'dependencia' => $request->dependencia,
                'personal_receptor' => $request->personal_receptor,
                'legajo_receptor' => $request->legajo_receptor,
                'personal_entrega' => $request->personal_entrega,
                'legajo_entrega' => $request->legajo_entrega,
                'motivo_operativo' => $request->motivo_operativo,
                'observaciones' => $request->observaciones,
                'rutas_imagenes' => json_encode($rutasImagenes)
            ]);

            // Actualizar bodycams: liberar las anteriores
            $bodycamsAnteriores = $entrega->bodycams->pluck('id')->toArray();
            foreach ($bodycamsAnteriores as $bodycamId) {
                if (!in_array($bodycamId, $request->bodycams_seleccionadas)) {
                    Bodycam::find($bodycamId)->update(['estado' => 'disponible']);
                }
            }

            // Eliminar detalles anteriores y crear nuevos
            $entrega->detalleEntregas()->delete();

            foreach ($request->bodycams_seleccionadas as $bodycamId) {
                DetalleEntregaBodycam::create([
                    'entrega_id' => $entrega->id,
                    'bodycam_id' => $bodycamId
                ]);

                Bodycam::find($bodycamId)->update(['estado' => 'entregada']);
            }

            DB::commit();

            return redirect()->route('entrega-bodycams.show', $entrega->id)
                ->with('success', 'Acta de entrega actualizada exitosamente');

        } catch (Exception $e) {
            DB::rollback();
            Log::error('Error al actualizar entrega: ' . $e->getMessage());

            return redirect()->back()
                ->with('error', 'Error al actualizar el acta: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function generarDocumento($id)
    {
        $entrega = EntregaBodycam::with([
            'bodycams',
            'detalleEntregas.bodycam'
        ])->findOrFail($id);

        $templateName = 'template_entrega_bodycams.docx';
        $templatePath = storage_path('app/templates/' . $templateName);

        if (!file_exists($templatePath)) {
            return redirect()->back()->with('error', 'Template de documento no encontrado: ' . $templateName);
        }

        try {
            $templateProcessor = new TemplateProcessor($templatePath);

            // Reemplazar variables principales
            $templateProcessor->setValue('DIA', $entrega->fecha_entrega->format('d'));

            // Convertir mes a español
            $meses = [
                'January' => 'Enero', 'February' => 'Febrero', 'March' => 'Marzo',
                'April' => 'Abril', 'May' => 'Mayo', 'June' => 'Junio',
                'July' => 'Julio', 'August' => 'Agosto', 'September' => 'Septiembre',
                'October' => 'Octubre', 'November' => 'Noviembre', 'December' => 'Diciembre'
            ];
            $mesIngles = $entrega->fecha_entrega->format('F');
            $mesEspanol = $meses[$mesIngles] ?? $mesIngles;

            $templateProcessor->setValue('MES', $mesEspanol);
            $templateProcessor->setValue('ANIO', $entrega->fecha_entrega->format('Y'));
            $templateProcessor->setValue('HORA', Carbon::parse($entrega->hora_entrega)->format('H:i'));

            // Variables de cantidad y descripción
            $cantidadBodycams = $entrega->bodycams->count();
            $templateProcessor->setValue('CANTIDAD_BODYCAMS', $cantidadBodycams);
            $templateProcessor->setValue('CANTIDAD_BODYCAMS_LETRAS', $this->numeroALetras($cantidadBodycams));

            $primeraBodycam = $entrega->bodycams->first();
            if ($primeraBodycam) {
                $templateProcessor->setValue('MARCA', $primeraBodycam->marca ?? 'N/A');
                $templateProcessor->setValue('MODELO', $primeraBodycam->modelo ?? 'N/A');
            } else {
                $templateProcessor->setValue('MARCA', 'N/A');
                $templateProcessor->setValue('MODELO', 'N/A');
            }

            // Información del operativo
            $templateProcessor->setValue('DEPENDENCIA', $entrega->dependencia ?? 'SIN DEPENDENCIA');
            $templateProcessor->setValue('MOTIVO', $entrega->motivo_operativo ?? 'Operativo dispuesto por la Superioridad');

            // Información del receptor y entregador
            $templateProcessor->setValue('PERSONAL_RECEPTOR', $entrega->personal_receptor ?? '');
            $templateProcessor->setValue('LEGAJO_RECEPTOR', $entrega->legajo_receptor ?? '');
            $templateProcessor->setValue('PERSONAL_ENTREGA', $entrega->personal_entrega ?? '');
            $templateProcessor->setValue('LEGAJO_ENTREGA', $entrega->legajo_entrega ?? '');

            // Preparar datos de las bodycams
            $bodycamsData = [];
            $contador = 1;

            foreach ($entrega->bodycams as $bodycam) {
                $bodycamsData[] = [
                    'NUMERO' => $contador++,
                    'CODIGO' => $bodycam->codigo ?? 'N/A',
                    'SERIE' => $bodycam->numero_serie ?? 'N/A',
                    'TARJETA_SD' => $bodycam->numero_tarjeta_sd ?? 'N/A'
                ];
            }

            // Clonar filas de la tabla de bodycams
            if (!empty($bodycamsData)) {
                $templateProcessor->cloneRowAndSetValues('NUMERO', $bodycamsData);
            }

            // Crear nombre del archivo
            $dependenciaFolder = $this->limpiarNombreCarpeta($entrega->dependencia ?? 'GENERAL');
            $fileName = 'entrega_bodycams_' . $entrega->id . '_' . date('Ymd_His') . '_' .
                $entrega->bodycams->count() . '_unidades_' .
                $this->acortarNombreDependencia($entrega->dependencia ?? 'GENERAL') . '.docx';

            // Guardar en TEMP local para descarga
            $tempPath = storage_path('app/temp/' . $fileName);
            if (!file_exists(dirname($tempPath))) {
                mkdir(dirname($tempPath), 0755, true);
            }
            $templateProcessor->saveAs($tempPath);

            // Copiar a carpeta de red
            $baseNetworkPath = '\\\\193.169.1.247\\Comp_Tecnica$\\01-Técnica 911 Doc\\01-Documentos\\Entregas Bodycams';
            $fechaFolder = $entrega->fecha_entrega->format('Ymd') . '_' .
                Carbon::parse($entrega->hora_entrega)->format('Hi');

            $fullPath = $baseNetworkPath . '\\' . $dependenciaFolder . '\\' . $fechaFolder;
            if (!file_exists($fullPath)) {
                mkdir($fullPath, 0755, true);
            }

            $networkFile = $fullPath . '\\' . $fileName;
            $copiadoExitoso = false;
            if (@copy($tempPath, $networkFile)) {
                Log::info("Documento de bodycams generado y guardado en red: {$networkFile}");
                $copiadoExitoso = true;
            } else {
                Log::warning("No se pudo copiar el documento a red: {$networkFile}");
            }

            $entrega->ruta_archivo = $networkFile;
            $entrega->save();

            // Descargar automáticamente
            if (file_exists($tempPath)) {
                $mensaje = 'Acta de entrega de bodycams generada exitosamente';
                if (!$copiadoExitoso) {
                    $mensaje .= ' (Nota: No se pudo guardar en la carpeta de red, pero el documento está disponible para descarga)';
                }

                return response()->download($tempPath, $fileName, [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                ])->deleteFileAfterSend(true);
            } else {
                return redirect()->route('entrega-bodycams.index')
                    ->with('error', 'Error: No se pudo generar el archivo para descarga.');
            }

        } catch (Exception $e) {
            Log::error("Error al generar documento de bodycams: " . $e->getMessage());
            return redirect()->back()->with('error', 'Error al generar el documento: ' . $e->getMessage());
        }
    }

    public function devolver(Request $request, $id)
    {
        $entrega = EntregaBodycam::findOrFail($id);

        // Obtener bodycams pendientes de devolución
        $bodycamsPendientes = $entrega->bodycamsPendientes()->get();

        if ($bodycamsPendientes->isEmpty()) {
            return redirect()->back()->with('error', 'No hay bodycams pendientes de devolución');
        }

        return view('entregas.entregas-bodycams.devolver', compact('entrega', 'bodycamsPendientes'));
    }

    public function procesarDevolucion(Request $request, $id)
    {
        $entrega = EntregaBodycam::findOrFail($id);

        $request->validate([
            'fecha_devolucion' => 'required|date',
            'hora_devolucion' => 'required',
            'personal_devuelve' => 'nullable|string|max:255',
            'legajo_devuelve' => 'nullable|string|max:50',
            'bodycams_devolver' => 'required|array|min:1',
            'bodycams_devolver.*' => 'exists:bodycams,id',
            'observaciones' => 'nullable|string',
            'imagen1' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'imagen2' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'imagen3' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'archivo' => 'nullable|mimes:pdf,doc,docx,xlsx,zip,rar|max:2048'
        ]);

        try {
            DB::beginTransaction();

            // Crear la devolución
            $devolucion = DevolucionBodycam::create([
                'entrega_id' => $entrega->id,
                'fecha_devolucion' => $request->fecha_devolucion,
                'hora_devolucion' => $request->hora_devolucion,
                'personal_devuelve' => $request->personal_devuelve,
                'legajo_devuelve' => $request->legajo_devuelve,
                'observaciones' => $request->observaciones,
                'usuario_creador' => auth()->user()->name
            ]);

            // Procesar imágenes
            $rutasImagenes = [];
            for ($i = 1; $i <= 3; $i++) {
                $inputName = 'imagen' . $i;
                if ($request->hasFile($inputName)) {
                    $rutaImagen = $request->file($inputName)->store('', 'anexos');
                    $rutasImagenes[] = 'anexos/' . $rutaImagen;
                    Log::info("Nueva imagen {$i} subida: anexos/{$rutaImagen}");
                }
            }

            // Manejo del archivo adjunto
            if ($request->hasFile('archivo')) {
                $rutaArchivo = $request->file('archivo')->store('', 'anexos');
                $rutasImagenes[] = 'anexos/' . $rutaArchivo;
                Log::info("Nuevo archivo adjunto subido: anexos/{$rutaArchivo}");
            }

            $devolucion->update(['rutas_imagenes' => json_encode($rutasImagenes)]);

            // Crear el detalle de la devolución
            foreach ($request->bodycams_devolver as $bodycamId) {
                DetalleDevolucionBodycam::create([
                    'devolucion_id' => $devolucion->id,
                    'bodycam_id' => $bodycamId
                ]);

                // Actualizar estado de la bodycam a 'disponible'
                Bodycam::find($bodycamId)->update(['estado' => 'disponible']);
            }

            // Actualizar el estado de la entrega
            $entrega->actualizarEstado();

            DB::commit();

            return redirect()->route('entrega-bodycams.show', $entrega->id)
                ->with('success', 'Devolución registrada exitosamente');

        } catch (Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->with('error', 'Error al procesar la devolución: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $entrega = EntregaBodycam::findOrFail($id);

            DB::beginTransaction();

            // Liberar bodycams antes de eliminar
            foreach ($entrega->bodycams as $bodycam) {
                $bodycam->update(['estado' => 'disponible']);
            }

            $entrega->delete();

            DB::commit();

            return redirect()->route('entrega-bodycams.index')
                ->with('success', 'Acta de entrega eliminada exitosamente');

        } catch (Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->with('error', 'Error al eliminar el acta: ' . $e->getMessage());
        }
    }

    // Métodos auxiliares
    private function acortarNombreDependencia($nombre)
    {
        $abreviaciones = [
            'Dirección General de Operaciones y Seguridad Pública' => 'DGOSP',
            'Dirección General de Investigaciones' => 'DGI',
            'Comisaría Primera' => 'Cria1',
            'Comisaría Segunda' => 'Cria2',
            'Comando Radioeléctrico' => 'CmdRadio',
            'División Comunicaciones' => 'DivCom'
        ];

        if (isset($abreviaciones[$nombre])) {
            return $abreviaciones[$nombre];
        }

        $palabras = explode(' ', $nombre);
        $abreviacion = '';

        foreach ($palabras as $palabra) {
            $palabra = trim($palabra);
            if (strlen($palabra) > 2 && !in_array(strtolower($palabra), ['de', 'del', 'la', 'las', 'el', 'los', 'y', 'e'])) {
                $abreviacion .= strtoupper(substr($palabra, 0, 3));
            }
        }

        $abreviacion = substr($abreviacion, 0, 15);
        return $this->limpiarNombreCarpeta($abreviacion) ?: 'DEPT';
    }

    private function limpiarNombreCarpeta($nombre)
    {
        $caracteresProhibidos = ['<', '>', ':', '"', '|', '?', '*', '/', '\\'];
        $nombreLimpio = str_replace($caracteresProhibidos, '-', $nombre);
        $nombreLimpio = rtrim($nombreLimpio, '.');
        $nombreLimpio = substr($nombreLimpio, 0, 200);
        return trim($nombreLimpio);
    }

    private function numeroALetras($numero)
    {
        $numeros = [
            1 => 'UNA', 2 => 'DOS', 3 => 'TRES', 4 => 'CUATRO', 5 => 'CINCO',
            6 => 'SEIS', 7 => 'SIETE', 8 => 'OCHO', 9 => 'NUEVE', 10 => 'DIEZ',
            11 => 'ONCE', 12 => 'DOCE', 13 => 'TRECE', 14 => 'CATORCE', 15 => 'QUINCE',
            16 => 'DIECISÉIS', 17 => 'DIECISIETE', 18 => 'DIECIOCHO', 19 => 'DIECINUEVE', 20 => 'VEINTE'
        ];

        return $numeros[$numero] ?? (string) $numero;
    }
}
