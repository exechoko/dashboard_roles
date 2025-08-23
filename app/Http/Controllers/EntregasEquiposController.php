<?php

namespace App\Http\Controllers;

use App\Models\Destino;
use App\Models\DetalleDevolucionEquipo;
use App\Models\DetalleEntregaAccesorio;
use App\Models\DetalleEntregaEquipo;
use App\Models\DevolucionEquipo;
use App\Models\EntregaEquipo;
use App\Models\FlotaGeneral;
use Carbon\Carbon;
use DB;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Log;
use PhpOffice\PhpWord\TemplateProcessor;
use PhpOffice\PhpWord\IOFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
            ->whereHas('recurso', function ($query) {
                $query->where('nombre', 'Unidad Operativa Móvil');
            })
            ->with('equipo')
            ->orderBy('equipo_id', 'asc')
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
                'personal_receptor' => $request->personal_receptor ?? '',
                'legajo_receptor' => $request->legajo_receptor ?? '',
                'personal_entrega' => $request->personal_entrega ?? '',
                'legajo_entrega' => $request->legajo_entrega ?? '',
                'motivo_operativo' => $request->motivo_operativo,
                'con_2_baterias' => $request->has('con_segunda_bateria') ? true : false,
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

            // Crear accesorios si se especificaron
            if ($request->has('cunas')) {
                foreach ($request->cunas as $cuna) {
                    DetalleEntregaAccesorio::create([
                        'entrega_id' => $entrega->id,
                        'tipo_accesorio' => DetalleEntregaAccesorio::TIPO_CUNA_CARGADORA,
                        'cantidad' => $cuna['cantidad'],
                        'marca' => $cuna['marca'],
                        'numero_serie' => $cuna['numero_serie'] ?? null,
                        'observaciones' => $cuna['observaciones'] ?? null
                    ]);
                }
            }

            // Crear transformadores si se especificaron
            if ($request->has('cantidad_transformadores') && $request->cantidad_transformadores > 0) {
                DetalleEntregaAccesorio::create([
                    'entrega_id' => $entrega->id,
                    'tipo_accesorio' => DetalleEntregaAccesorio::TIPO_TRANSFORMADOR,
                    'cantidad' => $request->cantidad_transformadores,
                    'marca' => null, // Transformadores no tienen marca
                    'numero_serie' => null, // Transformadores no tienen número de serie
                    'observaciones' => null // Transformadores no tienen observaciones
                ]);
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
        $entrega = EntregaEquipo::with([
            'equipos',
            'detalleEntregas.equipo',
            'accesorios',
            'cunasCargadoras',
            'transformadores'
        ])->findOrFail($id);
        return view('entregas.entregas-equipos.show', compact('entrega'));
    }

    public function edit($id)
    {
        $entrega = EntregaEquipo::with([
            'equipos',
            'cunasCargadoras',
            'transformadores',
            'accesorios'
        ])->findOrFail($id);
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
                'con_2_baterias' => $request->has('con_segunda_bateria') ? true : false,
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

            // Eliminar accesorios anteriores
            $entrega->accesorios()->delete();

            // Crear accesorios si se especificaron
            if ($request->has('cunas')) {
                foreach ($request->cunas as $cuna) {
                    DetalleEntregaAccesorio::create([
                        'entrega_id' => $entrega->id,
                        'tipo_accesorio' => DetalleEntregaAccesorio::TIPO_CUNA_CARGADORA,
                        'cantidad' => $cuna['cantidad'],
                        'marca' => $cuna['marca'],
                        'numero_serie' => $cuna['numero_serie'] ?? null,
                        'observaciones' => $cuna['observaciones'] ?? null
                    ]);
                }
            }

            // Crear transformadores si se especificaron
            if ($request->has('cantidad_transformadores') && $request->cantidad_transformadores > 0) {
                DetalleEntregaAccesorio::create([
                    'entrega_id' => $entrega->id,
                    'tipo_accesorio' => DetalleEntregaAccesorio::TIPO_TRANSFORMADOR,
                    'cantidad' => $request->cantidad_transformadores,
                    'marca' => null,
                    'numero_serie' => null,
                    'observaciones' => null
                ]);
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
        $entrega = EntregaEquipo::with([
            'equipos.equipo',
            'detalleEntregas.equipo',
            'cunasCargadoras',
            'transformadores'
        ])->findOrFail($id);

        // Determinar qué template usar basado en los accesorios
        $tieneCunas = $entrega->cunasCargadoras()->exists();
        $tieneTransformadores = $entrega->transformadores()->exists();
        $tieneAccesorios = $tieneCunas || $tieneTransformadores;

        // Seleccionar el template apropiado
        if ($entrega->con_2_baterias) {
            $templateName = $tieneAccesorios
                ? 'template_entrega_equipos_2_bateria_cuna_trafo.docx'
                : 'template_entrega_equipos_2_baterias.docx';
        } else {
            $templateName = $tieneAccesorios
                ? 'template_entrega_equipos_cuna_trafo.docx'
                : 'template_entrega_equipos.docx';
        }

        $templatePath = storage_path('app/templates/' . $templateName);

        if (!file_exists($templatePath)) {
            return redirect()->back()->with('error', 'Template de documento no encontrado: ' . $templateName);
        }
        //dd($templatePath);

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
            $templateProcessor->setValue('HORA', Carbon::parse($entrega->hora_entrega)->format('H:i'));

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
            $templateProcessor->setValue('DEPENDENCIA', $entrega->dependencia ?? 'SIN DEPENDENCIA');
            $templateProcessor->setValue('MOTIVO', $entrega->motivo_operativo ?? 'Operativo dispuesto por la Superioridad');

            // Información del receptor
            $templateProcessor->setValue('PERSONAL_RECEPTOR', $entrega->personal_receptor ?? '');
            $templateProcessor->setValue('LEGAJO_RECEPTOR', $entrega->legajo_receptor ?? '');

            // Información del entregador
            $templateProcessor->setValue('PERSONAL_ENTREGA', $entrega->personal_entrega ?? '');
            $templateProcessor->setValue('LEGAJO_ENTREGA', $entrega->legajo_entrega ?? '');

            // Preparar datos específicos según el template
            $equiposData = [];
            $contador = 1;
            $totalSegundasBaterias = 0;

            // Calcular segundas baterías (independiente de accesorios)
            foreach ($entrega->equipos as $equipo) {
                if (!empty($equipo->equipo->numero_segunda_bateria)) {
                    $totalSegundasBaterias++;
                }
            }

            if ($tieneAccesorios) {
                // Para template con accesorios
                $totalCunas = $entrega->cunasCargadoras->sum('cantidad');

                foreach ($entrega->equipos as $index => $equipo) {
                    $cuna = $entrega->cunasCargadoras[$index] ?? null;
                    $equipoData = [
                        'NUMERO' => $contador++,
                        'ID' => $equipo->equipo->nombre_issi ?? 'N/A',
                        'TEI' => $equipo->equipo->tei ?? 'N/A',
                        'BATERIA' => $equipo->equipo->numero_bateria ?? 'N/A',
                        'CUNA' => $cuna->numero_serie ?? 'N/A',
                    ];

                    // Agregar BATERIA_2 solo si el template la necesita (con 2 baterías)
                    if ($entrega->con_2_baterias) {
                        $equipoData['BATERIA_2'] = $equipo->equipo->numero_segunda_bateria ?? 'N/A';
                    }

                    $equiposData[] = $equipoData;
                }

                // Variables específicas del template con accesorios
                $templateProcessor->setValue('CANTIDAD_CUNAS', $totalCunas);
                $templateProcessor->setValue('CANTIDAD_CUNAS_LETRAS', $this->numeroALetras($totalCunas));
                $templateProcessor->setValue('MARCA_CUNA', $entrega->cunasCargadoras->first()->marca ?? 'N/A');
                $templateProcessor->setValue('MODELO_CUNA', $entrega->cunasCargadoras->first()->modelo ?? 'N/A');

                // Variables de segundas baterías (si aplica)
                if ($entrega->con_2_baterias) {
                    $templateProcessor->setValue('CANTIDAD_SEGUNDA_BATERIAS', $totalSegundasBaterias);
                    $templateProcessor->setValue('CANTIDAD_SEGUNDA_BATERIAS_LETRAS', $this->numeroALetras($totalSegundasBaterias));
                }

            } elseif ($entrega->con_2_baterias) {
                // Para template de 2 baterías SIN accesorios
                foreach ($entrega->equipos as $equipo) {
                    $equiposData[] = [
                        'NUMERO' => $contador++,
                        'ID' => $equipo->equipo->nombre_issi ?? 'N/A',
                        'TEI' => $equipo->equipo->tei ?? 'N/A',
                        'BATERIA' => $equipo->equipo->numero_bateria ?? 'N/A',
                        'BATERIA_2' => $equipo->equipo->numero_segunda_bateria ?? 'N/A'
                    ];
                }

                // Variables específicas para el template de 2 baterías
                $templateProcessor->setValue('CANTIDAD_BATERIAS_LETRA', $this->numeroALetras($cantidadEquipos));
                $templateProcessor->setValue('CANTIDAD_BATERIAS', $cantidadEquipos);
                $templateProcessor->setValue('CANTIDAD_SEGUNDA_BATERIAS', $totalSegundasBaterias);
                $templateProcessor->setValue('CANTIDAD_SEGUNDA_BATERIAS_LETRAS', $this->numeroALetras($totalSegundasBaterias));

            } else {
                // Para template básico sin accesorios ni segundas baterías
                foreach ($entrega->equipos as $equipo) {
                    $equiposData[] = [
                        'NUMERO' => $contador++,
                        'ID' => $equipo->equipo->nombre_issi ?? 'N/A',
                        'TEI' => $equipo->equipo->tei ?? 'N/A',
                        'BATERIA' => $equipo->equipo->numero_bateria ?? 'N/A'
                    ];
                }
            }

            // Clonar filas de la tabla de equipos
            if (!empty($equiposData)) {
                $templateProcessor->cloneRowAndSetValues('NUMERO', $equiposData);
            }

            // Limpiar dependencia para nombre de carpeta válido en Windows
            $dependenciaNombre = $entrega->dependencia ?? 'DIRECCIÓN INSTITUTOS POLICIALES';
            $dependenciaFolder = $this->limpiarNombreCarpeta($dependenciaNombre);

            // Crear sufijo del nombre de archivo basado en accesorios
            $sufijos = [];
            if ($tieneCunas) {
                $cantidadCunas = $entrega->cunasCargadoras->sum('cantidad');
                $sufijos[] = $cantidadCunas . '_cunas';
            }
            if ($tieneTransformadores) {
                $cantidadTrafos = $entrega->transformadores->sum('cantidad');
                $sufijos[] = $cantidadTrafos . '_trafos';
            }

            $sufijoAccesorios = !empty($sufijos) ? '_' . implode('_', $sufijos) : '';

            // Nombre del archivo
            $fileName = 'entrega_' . $entrega->id . '_' . date('Ymd_His') . '_' .
                $entrega->equipos->count() . '_equipos' . $sufijoAccesorios . '_' .
                $this->acortarNombreDependencia($dependenciaNombre) . '.docx';

            // ----------- (1) Guardar en TEMP local para descarga ---------
            $tempPath = storage_path('app/temp/' . $fileName);
            if (!file_exists(dirname($tempPath))) {
                mkdir(dirname($tempPath), 0755, true);
            }
            $templateProcessor->saveAs($tempPath);

            // ----------- (2) Copiar a carpeta de red ---------------------
            $baseNetworkPath = '\\\\193.169.1.247\\Comp_Tecnica$\\01-Técnica 911 Doc\\01-Documentos\\U.M. - Acontecimientos - Eventos\\Entregas CAR911';

            $fechaFolder = $entrega->fecha_entrega->format('Ymd') . '_' .
                Carbon::parse($entrega->hora_entrega)->format('Hi');

            $fullPath = $baseNetworkPath . '\\' . $dependenciaFolder . '\\' . $fechaFolder;
            if (!file_exists($fullPath)) {
                mkdir($fullPath, 0755, true);
            }

            $networkFile = $fullPath . '\\' . $fileName;
            // Copiar el archivo local al de red
            if (@copy($tempPath, $networkFile)) {
                Log::info("Documento generado con template '{$templateName}' y guardado en red: {$networkFile}");
            } else {
                Log::warning("No se pudo copiar el documento a red: {$networkFile}");
            }

            $entrega->ruta_archivo = $networkFile;
            $entrega->save();

            $mensaje = $tieneAccesorios
                ? 'Acta de entrega con accesorios generada y guardada exitosamente'
                : 'Acta de entrega generada y guardada exitosamente';

            return redirect()->route('entrega-equipos.index')
                ->with('success', $mensaje);

        } catch (Exception $e) {
            Log::error("Error al generar documento: " . $e->getMessage());
            return redirect()->back()->with('error', 'Error al generar el documento: ' . $e->getMessage());
        }
    }

    /**
     * Acorta el nombre de la dependencia para nombres de archivo más cortos
     */
    private function acortarNombreDependencia($nombre)
    {
        // Array de abreviaciones comunes
        $abreviaciones = [
            'Dirección General de Operaciones y Seguridad Pública' => 'DGOSP',
            'Dirección General de Investigaciones' => 'DGI',
            'Dirección General de Seguridad' => 'DGS',
            'Dirección de Investigaciones' => 'DI',
            'División Investigaciones' => 'DivInv',
            'Comisaría Primera' => 'Cria1',
            'Comisaría Segunda' => 'Cria2',
            'Comisaría Tercera' => 'Cria3',
            'Comisaría Cuarta' => 'Cria4',
            'Comisaría Quinta' => 'Cria5',
            'Comisaría Sexta' => 'Cria6',
            'Comisaría Séptima' => 'Cria7',
            'Comisaría Octava' => 'Cria8',
            'Comisaría Novena' => 'Cria9',
            'Comisaría Décima' => 'Cria10',
            'Comisaría Undécima' => 'Cria11',
            'Comisaría Duodécima' => 'Cria12',
            'Comando Radioeléctrico' => 'CmdRadio',
            'Infantería' => 'Inf',
            'Motorizada' => 'Mot',
            'Dirección Institutos Policiales' => 'DIP',
            'Instituto de Formación Policial' => 'IFP',
            'Escuela de Cadetes' => 'EscCad',
            'División Canes' => 'DivCanes',
            'División Comunicaciones' => 'DivCom',
            'División Bomberos' => 'DivBomb',
            'Unidad Regional' => 'UR',
            'Seccional' => 'Secc',
            'Destacamento' => 'Dest'
        ];

        // Buscar coincidencia exacta primero
        if (isset($abreviaciones[$nombre])) {
            return $abreviaciones[$nombre];
        }

        // Buscar coincidencias parciales
        foreach ($abreviaciones as $nombreCompleto => $abreviacion) {
            if (stripos($nombre, $nombreCompleto) !== false) {
                return $abreviacion;
            }
        }

        // Si no encuentra coincidencias, crear abreviación genérica
        $palabras = explode(' ', $nombre);
        $abreviacion = '';

        foreach ($palabras as $palabra) {
            $palabra = trim($palabra);
            if (strlen($palabra) > 2 && !in_array(strtolower($palabra), ['de', 'del', 'la', 'las', 'el', 'los', 'y', 'e'])) {
                $abreviacion .= strtoupper(substr($palabra, 0, 3));
            }
        }

        // Limitar longitud final y limpiar
        $abreviacion = substr($abreviacion, 0, 15);
        $abreviacion = $this->limpiarNombreCarpeta($abreviacion);

        return $abreviacion ?: 'DEPT';
    }

    /** * Limpia el nombre de la dependencia para usar como nombre de carpeta */
    private function limpiarNombreCarpeta($nombre)
    {
        // Caracteres no permitidos en nombres de carpetas de Windows
        $caracteresProhibidos = ['<', '>', ':', '"', '|', '?', '*', '/', '\\'];
        // Reemplazar caracteres prohibidos por guiones
        $nombreLimpio = str_replace($caracteresProhibidos, '-', $nombre);
        // Eliminar puntos al final (no permitidos en Windows)
        $nombreLimpio = rtrim($nombreLimpio, '.');
        // Limitar longitud (Windows tiene límite de 255 caracteres para nombres de carpeta)
        $nombreLimpio = substr($nombreLimpio, 0, 200);
        return trim($nombreLimpio);
    }

    public function descargarArchivo($id)
    {
        $entrega = EntregaEquipo::findOrFail($id);

        if (!$entrega->ruta_archivo) {
            abort(404, 'Archivo no encontrado');
        }

        // Verificar si el archivo existe en la red
        if (!file_exists($entrega->ruta_archivo)) {
            return back()->with('error', 'El archivo no está disponible: ' . $entrega->ruta_archivo);
        }

        $nombreArchivo = basename($entrega->ruta_archivo);

        return response()->download($entrega->ruta_archivo, $nombreArchivo);
    }

    public function previsualizar($id)
    {
        $entrega = EntregaEquipo::findOrFail($id);
        $rutaArchivoUNC = $entrega->ruta_archivo; // La ruta \\193.169.1.247\...

        if (!file_exists($rutaArchivoUNC)) {
            return redirect()->back()->with('error', 'El archivo no se encuentra.');
        }

        // Asegúrate de que PHP en tu servidor Windows pueda acceder a esta ruta UNC
        // Puede que necesites configurar PHP para que el usuario bajo el que corre
        // el servidor web (e.g., IIS, Apache, Nginx) tenga permisos de red a la carpeta compartida.

        $filename = basename($rutaArchivoUNC);
        $mimeType = Storage::mimeType($rutaArchivoUNC); // O usar finfo para obtener el tipo MIME

        // Forzar la descarga
        // return response()->download($rutaArchivoUNC, $filename);

        // O intentar mostrar en el navegador (útil para PDF, imágenes)
        return new StreamedResponse(function () use ($rutaArchivoUNC) {
            $stream = fopen($rutaArchivoUNC, 'r');
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
            'Content-Length' => filesize($rutaArchivoUNC),
        ]);
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
            'observaciones' => 'nullable|string',
            'imagen1' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'imagen2' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'imagen3' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'archivo' => 'nullable|mimes:pdf,doc,docx,xlsx,zip,rar|max:2048'
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

            // Obtener las rutas de imágenes existentes
            $rutasImagenesExistentes = json_decode($devolucion->rutas_imagenes, true) ?? [];
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

            $devolucion->update(['rutas_imagenes' => json_encode($rutasImagenes)]);

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
