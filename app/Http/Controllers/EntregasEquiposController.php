<?php

namespace App\Http\Controllers;

use App\Models\Destino;
use App\Models\DetalleDevolucionEquipo;
use App\Models\DetalleEntregaEquipo;
use App\Models\DevolucionEquipo;
use App\Models\EntregaEquipo;
use App\Models\FlotaGeneral;
use DB;
use Exception;
use Illuminate\Http\Request;
use Log;
use PhpOffice\PhpWord\TemplateProcessor;

class EntregasEquiposController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $query = EntregaEquipo::with(['equipos', 'detalleEntregas']);

        // Aplicar filtros de búsqueda
        if ($request->filled('tei')) {
            $query->buscarPorTei($request->tei);
        }

        if ($request->filled('issi')) {
            $query->buscarPorIssi($request->issi);
        }

        if ($request->filled('fecha')) {
            $query->buscarPorFecha($request->fecha);
        }

        if ($request->filled('dependencia')) {
            $query->buscarPorDependencia($request->dependencia);
        }

        $entregas = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('entregas.entregas-equipos.index', compact('entregas'));
    }

    public function create()
    {
        // Obtener equipos portatiles disponibles (no entregados actualmente)
        $equiposDisponibles = FlotaGeneral::whereDoesntHave('entregasActivas')
            ->whereHas('equipo.tipo_terminal.tipo_uso', function ($query) {
                $query->where('uso', 'portatil');
            })
            ->with('equipo')
            ->get();

        //Destinos
        $destinos = Destino::all();

        //dd($equiposDisponibles);
        return view('entregas.entregas-equipos.crear', compact('equiposDisponibles', 'destinos'));
    }

    public function store(Request $request)
    {
        //dd($request->all());
        $request->validate([
            'fecha_entrega' => 'required|date',
            'hora_entrega' => 'required',
            'dependencia' => 'required|string|max:255',
            'personal_receptor' => 'required|string|max:255',
            'legajo_receptor' => 'nullable|string|max:50',
            'personal_entrega' => 'required|string|max:255',
            'legajo_entrega' => 'nullable|string|max:50',
            'motivo_operativo' => 'required|string',
            'equipos_seleccionados' => 'required|array|min:1',
            'equipos_seleccionados.*' => 'exists:flota_general,id',
            'imagen1' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'imagen2' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'imagen3' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'archivo' => 'nullable|mimes:pdf,doc,docx,xlsx,zip,rar|max:2048'
        ]);

        // Obtener las rutas de imágenes existentes
        $rutasImagenesExistentes = [];
        $rutasImagenes = $rutasImagenesExistentes; // Empezar con las existentes

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

        // Si no hay nuevas imágenes, mantener las existentes
        if (empty($rutasImagenes) && !empty($rutasImagenesExistentes)) {
            $rutasImagenes = $rutasImagenesExistentes;
        } else if (!empty($rutasImagenes)) {
            // Si hay nuevas imágenes, solo usar las nuevas (reemplazar todas)
            // Si quieres mantener las existentes Y agregar las nuevas, usa:
            // $rutasImagenes = array_merge($rutasImagenesExistentes, $rutasImagenes);
        }

        try {
            DB::beginTransaction();

            // Crear la entrega principal
            $entrega = EntregaEquipo::create([
                //'numero_acta' => EntregaEquipo::generarNumeroActa(),
                'fecha_entrega' => $request->fecha_entrega,
                'hora_entrega' => $request->hora_entrega,
                'dependencia' => $request->dependencia,
                'personal_receptor' => $request->personal_receptor,
                'legajo_receptor' => $request->legajo_receptor,
                'personal_entrega' => $request->personal_entrega,
                'legajo_entrega' => $request->legajo_entrega,
                'motivo_operativo' => $request->motivo_operativo,
                'observaciones' => $request->observaciones,
                'rutas_imagenes' => json_encode($rutasImagenes),
                'usuario_creador' => auth()->user()->name
            ]);

            // Crear el detalle de entregas
            foreach ($request->equipos_seleccionados as $equipoId) {
                DetalleEntregaEquipo::create([
                    'entrega_id' => $entrega->id,
                    'equipo_id' => $equipoId //Es la id de la flota
                ]);

                // Actualizar el estado del equipo a 'entregado'
                FlotaGeneral::find($equipoId)->update(['estado' => 'entregado']);
            }

            DB::commit();

            return redirect()->route('entrega-equipos.show', $entrega->id)
                ->with('success', 'Acta de entrega creada exitosamente');

        } catch (Exception $e) {
            dd($e->getMessage());
            DB::rollback();
            return redirect()->back()
                ->with('error', 'Error al crear el acta de entrega: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function show($id)
    {
        $entrega = EntregaEquipo::with(['equipos', 'detalleEntregas.equipo'])->findOrFail($id);
        return view('entregas.entregas-equipos.show', compact('entrega'));
    }

    public function edit($id)
    {
        $entrega = EntregaEquipo::with('equipos')->findOrFail($id);
        // Obtener equipos portátiles disponibles (no entregados actualmente)
        // o que ya están asignados a esta entrega para poder editarlos
        $equiposDisponibles = FlotaGeneral::where(function ($query) use ($entrega) {
            // Equipos disponibles que son portátiles
            $query->whereDoesntHave('entregasActivas')
                ->whereHas('equipo.tipo_terminal.tipo_uso', function ($subQuery) {
                    $subQuery->where('uso', 'portatil');
                });
        })
            ->orWhere(function ($query) use ($entrega) {
                // O equipos que ya están en esta entrega (para poder editarlos)
                $query->whereIn('id', $entrega->equipos->pluck('id'))
                    ->whereHas('equipo.tipo_terminal.tipo_uso', function ($subQuery) {
                    $subQuery->where('uso', 'portatil');
                });
            })
            ->with('equipo')
            ->get();

        //Destinos
        $destinos = Destino::all();

        return view(
            'entregas.entregas-equipos.editar',
            compact(
                'entrega',
                'equiposDisponibles',
                'destinos'
            )
        );
    }

    public function update(Request $request, $id)
    {
        $entrega = EntregaEquipo::findOrFail($id);

        $request->validate([
            'fecha_entrega' => 'required|date',
            'hora_entrega' => 'required',
            'dependencia' => 'required|string|max:255',
            'personal_receptor' => 'required|string|max:255',
            'legajo_receptor' => 'nullable|string|max:50',
            'personal_entrega' => 'required|string|max:255',
            'legajo_entrega' => 'nullable|string|max:50',
            'motivo_operativo' => 'required|string',
            'equipos_seleccionados' => 'required|array|min:1',
            'equipos_seleccionados.*' => 'exists:flota_general,id',
            'imagen1' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'imagen2' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'imagen3' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'archivo' => 'nullable|mimes:pdf,doc,docx,xlsx,zip,rar|max:2048'
        ]);

        // Obtener las rutas de imágenes existentes
        $rutasImagenesExistentes = json_decode($entrega->rutas_imagenes, true) ?? [];
        $rutasImagenes = $rutasImagenesExistentes; // Empezar con las existentes

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

        // Si no hay nuevas imágenes, mantener las existentes
        if (empty($rutasImagenes) && !empty($rutasImagenesExistentes)) {
            $rutasImagenes = $rutasImagenesExistentes;
        } else if (!empty($rutasImagenes)) {
            // Si hay nuevas imágenes, solo usar las nuevas (reemplazar todas)
            // Si quieres mantener las existentes Y agregar las nuevas, usa:
            // $rutasImagenes = array_merge($rutasImagenesExistentes, $rutasImagenes);
        }

        try {
            DB::beginTransaction();

            // Actualizar la entrega principal
            $datosActualizacion = [
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
            ];

            $entrega->update($datosActualizacion);

            Log::info("Entrega {$id} actualizada. Imágenes existentes: " . count($rutasImagenesExistentes) .
                ", Nuevas imágenes: " . ($hayNuevasImagenes ? 'Sí' : 'No') .
                ", Total rutas: " . count($rutasImagenes));

            // Actualizar equipos: primero liberar los anteriores
            $equiposAnteriores = $entrega->equipos->pluck('id')->toArray();
            foreach ($equiposAnteriores as $equipoId) {
                if (!in_array($equipoId, $request->equipos_seleccionados)) {
                    FlotaGeneral::find($equipoId)->update(['estado' => 'disponible']);
                }
            }

            // Eliminar detalles anteriores y crear nuevos
            $entrega->detalleEntregas()->delete();

            foreach ($request->equipos_seleccionados as $equipoId) {
                DetalleEntregaEquipo::create([
                    'entrega_id' => $entrega->id,
                    'equipo_id' => $equipoId
                ]);

                FlotaGeneral::find($equipoId)->update(['estado' => 'entregado']);
            }

            DB::commit();

            return redirect()->route('entrega-equipos.show', $entrega->id)
                ->with('success', 'Acta de entrega actualizada exitosamente');

        } catch (Exception $e) {
            DB::rollback();
            Log::error('Error al actualizar entrega: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return redirect()->back()
                ->with('error', 'Error al actualizar el acta: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function generarDocumento($id)
    {
        $entrega = EntregaEquipo::with(['equipos', 'detalleEntregas.equipo'])->findOrFail($id);

        // Ruta al template de Word
        $templatePath = storage_path('app/templates/template_entrega_equipos.docx');

        if (!file_exists($templatePath)) {
            return redirect()->back()->with('error', 'Template de documento no encontrado');
        }

        try {
            $templateProcessor = new TemplateProcessor($templatePath);

            // Reemplazar variables principales del encabezado
            $templateProcessor->setValue('DIA', $entrega->fecha_entrega->format('d'));

            // Convertir mes a español
            $meses = [
                'January' => 'Enero',
                'February' => 'Febrero',
                'March' => 'Marzo',
                'April' => 'Abril',
                'May' => 'Mayo',
                'June' => 'Junio',
                'July' => 'Julio',
                'August' => 'Agosto',
                'September' => 'Septiembre',
                'October' => 'Octubre',
                'November' => 'Noviembre',
                'December' => 'Diciembre'
            ];
            $mesIngles = $entrega->fecha_entrega->format('F');
            $mesEspanol = $meses[$mesIngles] ?? $mesIngles;

            $templateProcessor->setValue('MES', $mesEspanol);
            $templateProcessor->setValue('ANIO', $entrega->fecha_entrega->format('Y'));
            $templateProcessor->setValue('HORA', \Carbon\Carbon::parse($entrega->hora_entrega)->format('H:i'));

            // Variables de cantidad y descripción
            $cantidadEquipos = $entrega->equipos->count();
            $templateProcessor->setValue('CANTIDAD_EQUIPOS', $cantidadEquipos);
            $templateProcessor->setValue('CANTIDAD_EQUIPOS_LETRAS', $this->numeroALetras($cantidadEquipos));

            $primerEquipo = $entrega->equipos->first();
            if ($primerEquipo) {
                $templateProcessor->setValue('MARCA', $primerEquipo->equipo->tipo_terminal->marca ?? 'N/A');
                $templateProcessor->setValue('MODELO', $primerEquipo->equipo->tipo_terminal->modelo ?? 'N/A');
            } else {
                $templateProcessor->setValue('MARCA', 'N/A');
                $templateProcessor->setValue('MODELO', 'N/A');
            }

            // Información del operativo
            $templateProcessor->setValue('DEPENDENCIA', $entrega->dependencia ?? 'DIRECCIÓN INSTITUTOS POLICIALES');
            $templateProcessor->setValue('MOTIVO', $entrega->motivo_operativo ?? 'Operativo dispuesto por la Superioridad');

            // Información del receptor
            $templateProcessor->setValue('PERSONAL_RECEPTOR', $entrega->personal_receptor ?? '');
            $templateProcessor->setValue('LEGAJO_RECEPTOR', $entrega->legajo_receptor ?? '');

            // Información del entregador
            $templateProcessor->setValue('PERSONAL_ENTREGA', $entrega->personal_entrega ?? '');
            $templateProcessor->setValue('LEGAJO_ENTREGA', $entrega->legajo_entrega ?? '');

            // Preparar datos de la tabla de equipos
            $equiposData = [];
            $contador = 1;

            foreach ($entrega->equipos as $equipo) {
                $equiposData[] = [
                    'NUMERO' => $contador++,
                    'ID' => $equipo->equipo->nombre_issi ?? 'N/A',
                    'TEI' => $equipo->equipo->tei ?? 'N/A',
                    'BATERIA' => $equipo->equipo->numero_bateria ?? 'N/A'
                ];
            }

            // Clonar filas de la tabla
            if (!empty($equiposData)) {
                $templateProcessor->cloneRowAndSetValues('NUMERO', $equiposData);
            }

            // Generar archivo temporal
            $fileName = 'recibo_entrega_equipos_' . $entrega->id . '_' . date('Y-m-d_H-i-s') . '.docx';
            $tempPath = storage_path('app/temp/' . $fileName);

            // Crear directorio temp si no existe
            if (!file_exists(dirname($tempPath))) {
                mkdir(dirname($tempPath), 0755, true);
            }

            $templateProcessor->saveAs($tempPath);

            // Registrar la generación del documento (opcional)
            Log::info("Documento generado: {$fileName} para entrega ID: {$id}");

            return response()->download($tempPath, $fileName)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Log::error("Error al generar documento: " . $e->getMessage());
            return redirect()->back()->with('error', 'Error al generar el documento: ' . $e->getMessage());
        }
    }

    /**
     * Convierte un número a su representación en letras
     */
    private function numeroALetras($numero)
    {
        $numeros = [
            1 => 'UNO',
            2 => 'DOS',
            3 => 'TRES',
            4 => 'CUATRO',
            5 => 'CINCO',
            6 => 'SEIS',
            7 => 'SIETE',
            8 => 'OCHO',
            9 => 'NUEVE',
            10 => 'DIEZ',
            11 => 'ONCE',
            12 => 'DOCE',
            13 => 'TRECE',
            14 => 'CATORCE',
            15 => 'QUINCE',
            16 => 'DIECISÉIS',
            17 => 'DIECISIETE',
            18 => 'DIECIOCHO',
            19 => 'DIECINUEVE',
            20 => 'VEINTE'
        ];

        if ($numero <= 20) {
            return $numeros[$numero] ?? (string) $numero;
        }

        return (string) $numero; // Para números mayores a 20, devolver el número
    }

    public function destroy($id)
    {
        try {
            $entrega = EntregaEquipo::findOrFail($id);

            DB::beginTransaction();

            // Liberar equipos antes de eliminar
            foreach ($entrega->equipos as $equipo) {
                $equipo->update(['estado' => 'disponible']);
            }

            $entrega->delete();

            DB::commit();

            return redirect()->route('entrega-equipos.index')
                ->with('success', 'Acta de entrega eliminada exitosamente');

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->with('error', 'Error al eliminar el acta: ' . $e->getMessage());
        }
    }

    // Reemplazar el método devolver existente
    public function devolver(Request $request, $id)
    {
        $entrega = EntregaEquipo::findOrFail($id);

        // Obtener equipos pendientes de devolución
        $equiposPendientes = $entrega->equiposPendientes()->get();

        if ($equiposPendientes->isEmpty()) {
            return redirect()->back()->with('error', 'No hay equipos pendientes de devolución');
        }

        return view('entregas.entregas-equipos.devolver', compact('entrega', 'equiposPendientes'));
    }

    // Procesar la devolución parcial
    public function procesarDevolucion(Request $request, $id)
    {
        $entrega = EntregaEquipo::findOrFail($id);

        $request->validate([
            'fecha_devolucion' => 'required|date',
            'hora_devolucion' => 'required',
            'personal_devuelve' => 'nullable|string|max:255',
            'legajo_devuelve' => 'nullable|string|max:50',
            'equipos_devolver' => 'required|array|min:1',
            'equipos_devolver.*' => 'exists:flota_general,id',
            'observaciones' => 'nullable|string'
        ]);

        try {
            DB::beginTransaction();

            // Crear la devolución
            $devolucion = DevolucionEquipo::create([
                'entrega_id' => $entrega->id,
                'fecha_devolucion' => $request->fecha_devolucion,
                'hora_devolucion' => $request->hora_devolucion,
                'personal_devuelve' => $request->personal_devuelve,
                'legajo_devuelve' => $request->legajo_devuelve,
                'observaciones' => $request->observaciones,
                'usuario_creador' => auth()->user()->name
            ]);

            // Crear el detalle de la devolución y actualizar estado de equipos
            foreach ($request->equipos_devolver as $equipoId) {
                DetalleDevolucionEquipo::create([
                    'devolucion_id' => $devolucion->id,
                    'equipo_id' => $equipoId
                ]);
            }

            // Actualizar el estado de la entrega
            $entrega->actualizarEstado();

            DB::commit();

            return redirect()->route('entrega-equipos.show', $entrega->id)
                ->with('success', 'Devolución registrada exitosamente');

        } catch (Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->with('error', 'Error al procesar la devolución: ' . $e->getMessage())
                ->withInput();
        }
    }

    // Mostrar detalles de una devolución específica
    public function mostrarDevolucion($entregaId, $devolucionId)
    {
        $entrega = EntregaEquipo::findOrFail($entregaId);
        $devolucion = DevolucionEquipo::with(['equipos', 'detalleDevoluciones.equipo'])
            ->where('entrega_id', $entregaId)
            ->findOrFail($devolucionId);

        return view('entregas.entregas-equipos.devolucion-detalle', compact('entrega', 'devolucion'));
    }

    // Eliminar una devolución (solo si es necesario)
    public function eliminarDevolucion(Request $request, $entregaId, $devolucionId)
    {
        $entrega = EntregaEquipo::findOrFail($entregaId);
        $devolucion = DevolucionEquipo::where('entrega_id', $entregaId)->findOrFail($devolucionId);

        try {
            DB::beginTransaction();

            // Volver a marcar equipos como entregados
            foreach ($devolucion->equipos as $equipo) {
                $equipo->update(['estado' => 'entregado']);
            }

            // Eliminar devolución
            $devolucion->delete();

            // Actualizar estado de la entrega
            $entrega->actualizarEstado();

            DB::commit();

            return redirect()->route('entrega-equipos.show', $entrega->id)
                ->with('success', 'Devolución eliminada exitosamente');

        } catch (Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->with('error', 'Error al eliminar la devolución: ' . $e->getMessage());
        }
    }

    // Método para devolver equipos
    /*public function devolver(Request $request, $id)
    {
        $entrega = EntregaEquipo::findOrFail($id);

        try {
            DB::beginTransaction();

            foreach ($entrega->equipos as $equipo) {
                $equipo->update(['estado' => 'disponible']);
            }

            $entrega->update([
                'estado' => 'devuelto',
                'observaciones' => $entrega->observaciones . '\n\nDevuelto el: ' . now()->format('d/m/Y H:i') . ' por: ' . auth()->user()->name
            ]);

            DB::commit();

            return redirect()->back()->with('success', 'Equipos devueltos exitosamente');

        } catch (Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Error al devolver equipos: ' . $e->getMessage());
        }
    }*/

    /**
     * Buscar equipos disponibles para AJAX
     */
    public function buscarEquipos(Request $request)
    {
        $query = FlotaGeneral::whereDoesntHave('entregasActivas');

        if ($request->filled('term')) {
            $term = $request->term;
            $query->where(function ($q) use ($term) {
                $q->where('tei', 'LIKE', "%{$term}%")
                    ->orWhere('issi', 'LIKE', "%{$term}%")
                    ->orWhere('id_equipo', 'LIKE', "%{$term}%");
            });
        }

        $equipos = $query->limit(50)->get();

        return response()->json($equipos->map(function ($equipo) {
            return [
                'id' => $equipo->id,
                'text' => "ID: {$equipo->id_equipo} | TEI: {$equipo->tei} | ISSI: {$equipo->issi}",
                'tei' => $equipo->tei,
                'issi' => $equipo->issi,
                'id_equipo' => $equipo->id_equipo,
                'numero_bateria' => $equipo->numero_bateria,
                'estado' => $equipo->estado
            ];
        }));
    }

    /**
     * Reportar equipos como perdidos
     */
    public function reportarPerdido(Request $request, $id)
    {
        $entrega = EntregaEquipo::findOrFail($id);

        $request->validate([
            'motivo_perdida' => 'required|string',
            'equipos_perdidos' => 'nullable|array',
            'equipos_perdidos.*' => 'exists:flota_general,id'
        ]);

        try {
            DB::beginTransaction();

            // Si no se especifican equipos, marcar todos como perdidos
            $equiposPerdidos = $request->equipos_perdidos ?? $entrega->equipos->pluck('id')->toArray();

            foreach ($equiposPerdidos as $equipoId) {
                FlotaGeneral::find($equipoId)->update(['estado' => 'perdido']);
            }

            $entrega->update([
                'estado' => 'perdido',
                'observaciones' => $entrega->observaciones . "\n\nReportado como perdido el: " .
                    now()->format('d/m/Y H:i') . " por: " . auth()->user()->name .
                    "\nMotivo: " . $request->motivo_perdida
            ]);

            DB::commit();

            return redirect()->back()->with('success', 'Equipos reportados como perdidos exitosamente');

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Error al reportar equipos: ' . $e->getMessage());
        }
    }

    /**
     * Duplicar una entrega existente
     */
    public function duplicar($id)
    {
        $entregaOriginal = EntregaEquipo::with('equipos')->findOrFail($id);
        $equiposDisponibles = FlotaGeneral::whereDoesntHave('entregasActivas')->get();

        return view('entregas.entregas-equipos.crear', [
            'equiposDisponibles' => $equiposDisponibles,
            'entregaOriginal' => $entregaOriginal
        ]);
    }

    /**
     * Dashboard de entregas
     */
    public function dashboard()
    {
        /*$stats = [
            'total_entregas' => EntregaEquipo::count(),
            'entregas_activas' => EntregaEquipo::where('estado', 'entregado')->count(),
            'entregas_devueltas' => EntregaEquipo::where('estado', 'devuelto')->count(),
            'equipos_perdidos' => EntregaEquipo::where('estado', 'perdido')->count(),
            'equipos_en_uso' => FlotaGeneral::where('estado', 'entregado')->count(),
            'equipos_disponibles' => FlotaGeneral::where('estado', 'disponible')->count(),
        ];

        $entregasRecientes = EntregaEquipo::with(['equipos'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $entregasPorMes = EntregaEquipo::selectRaw('MONTH(created_at) as mes, COUNT(*) as total')
            ->whereYear('created_at', date('Y'))
            ->groupBy('mes')
            ->orderBy('mes')
            ->get();

        return view('entregas.dashboard', compact('stats', 'entregasRecientes', 'entregasPorMes'));*/
    }

    /**
     * Exportar listado
     */
    public function exportar($formato, Request $request)
    {
        // Implementar exportación según el formato (excel/pdf)
        // Puedes usar Laravel Excel o DomPDF

        $query = EntregaEquipo::with(['equipos', 'detalleEntregas']);

        // Aplicar los mismos filtros que en index
        if ($request->filled('tei')) {
            $query->buscarPorTei($request->tei);
        }
        // ... otros filtros

        $entregas = $query->get();

        if ($formato === 'excel') {
            // return Excel::download(new EntregasExport($entregas), 'entregas.xlsx');
        } else {
            // return PDF::loadView('entregas.pdf', compact('entregas'))->download('entregas.pdf');
        }
    }
}
