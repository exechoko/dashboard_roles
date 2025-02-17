<?php

namespace App\Http\Controllers;

use App\Models\Destino;
use App\Models\Equipo;
use App\Models\Estado;
use App\Models\FlotaGeneral;
use App\Models\Recurso;
use App\Models\Historico;
use App\Models\TipoMovimiento;
use App\Models\TipoTerminal;
use App\Models\Vehiculo;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Calculation\Engine\BranchPruner;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\TemplateProcessor;
use PhpOffice\PhpWord\Writer\Word2007;
use Str;

class FlotaGeneralController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:ver-flota|crear-flota|editar-flota|borrar-flota')->only('index');
        $this->middleware('permission:crear-flota', ['only' => ['create', 'store']]);
        $this->middleware('permission:editar-flota', ['only' => ['edit', 'update']]);
        $this->middleware('permission:borrar-flota', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        $texto = trim($request->get('texto')); //trim quita espacios vacios

        //Busqueda por ISSI, TEI, Movil o Destino
        $flota = FlotaGeneral::whereHas('equipo', function ($query) use ($texto) {
            $query->where('issi', 'like', '%' . $texto . '%')
                ->orWhere('tei', 'like', '%' . $texto . '%');
        })->orWhereHas('recurso', function ($query1) use ($texto) {
            $query1->where('nombre', 'like', '%' . $texto . '%');
        })->orWhereHas('destino', function ($query2) use ($texto) {
            $query2->where('nombre', 'like', '%' . $texto . '%');
        })->orderBy('updated_at', 'desc')->paginate(50); //->get();//->orWhere('observaciones', 'LIKE', '%' . $texto . '%')->orderBy('id', 'asc')->get();

        // Itera sobre cada flota para obtener su último movimiento
        foreach ($flota as $f) {
            //dd('aca');
            $f->ultimo_movimiento = $f->ultimoMovimiento()->tipoMovimiento->nombre;
            $f->fecha_ultimo_mov = Carbon::parse($f->ultimoMovimiento()->fecha_asignacion)->format('d/m/Y H:i');
            $f->observaciones_ultimo_mov = $f->ultimoMovimiento()->observaciones;
        }

        return view('flota.index', compact('flota', 'texto'));
    }

    public function busquedaAvanzada(Request $request)
    {
        $texto = trim($request->get('texto'));
        $equipoId = $request->get('equipo_id');
        $recursoId = $request->get('recurso_id');
        $destinoId = $request->get('destino_id');
        $destinoActualId = $request->get('destino_actual_id');
        $tipoTerminalId = $request->get('tipo_terminal_id');
        $fechaAsignacion = $request->get('fecha_asignacion');
        $fechaDesasignacion = $request->get('fecha_desasignacion');
        $fechaRango = $request->get('fecha_rango');
        $ticketPer = $request->get('ticket_per');
        $observaciones = $request->get('observaciones');

        // Obtener equipos, recursos y otros datos necesarios.
        $equipos = Equipo::all(); // Ajusta según tu modelo
        $recursos = Recurso::all(); // Si es necesario, agrega esta línea
        $destinos = Destino::all(); // Si es necesario, agrega esta línea
        $tiposTerminal = TipoTerminal::all();

        $flota = FlotaGeneral::query();

        if ($texto) {
            $flota->whereHas('equipo', function ($query) use ($texto) {
                $query->where('issi', 'like', '%' . $texto . '%')
                    ->orWhere('tei', 'like', '%' . $texto . '%');
            })->orWhereHas('recurso', function ($query1) use ($texto) {
                $query1->where('nombre', 'like', '%' . $texto . '%');
            })->orWhereHas('destino', function ($query2) use ($texto) {
                $query2->where('nombre', 'like', '%' . $texto . '%');
            });
        }

        if ($equipoId) {
            $flota->where('equipo_id', $equipoId);
        }
        if ($recursoId) {
            $flota->where('recurso_id', $recursoId);
        }
        if ($destinoActualId) {
            $flota->whereHas('equipo.historico', function ($query) use ($destinoActualId) {
                $query->where('destino_id', $destinoActualId)
                    ->where('fecha_desasignacion', null);
            });
        }
        if ($destinoId) {
            $destino = Destino::find($destinoId);
            $categoria = null;
            $nombre = $destino->nombre;
            if (strpos($nombre, 'Dirección') === 0) {
                $categoria = 'direccion';
            } elseif (strpos($nombre, 'Departamental') === 0) {
                $categoria = 'departamental';
            } elseif (strpos($nombre, 'División') === 0) {
                $categoria = 'division';
            } elseif (strpos($nombre, 'Comisaría') === 0) {
                $categoria = 'comisaria';
            } elseif (strpos($nombre, 'Sección') === 0) {
                $categoria = 'seccion';
            }
            if ($categoria) {
                $destinosDependientes = $destino->destinosDependientes($categoria, $destinoId);
                $flota->whereIn('destino_id', $destinosDependientes);
            }
        }
        /*if ($destinoId) {
            $flota->where('destino_id', $destinoId);
        }*/
        if ($tipoTerminalId) {
            $flota->whereHas('equipo', function ($query) use ($tipoTerminalId) {
                $query->where('tipo_terminal_id', $tipoTerminalId);
            });
        }
        if ($fechaAsignacion) {
            $flota->whereDate('fecha_asignacion', $fechaAsignacion);
        }
        if ($fechaDesasignacion) {
            $flota->whereDate('fecha_desasignacion', $fechaDesasignacion);
        }
        // Filtrar por rango de fechas en la tabla historico
        if ($fechaRango) {
            list($fechaInicio, $fechaFin) = explode(' - ', $fechaRango);

            // Convertir las fechas a objetos Carbon
            $fechaInicio = Carbon::parse($fechaInicio)->startOfDay();
            $fechaFin = Carbon::parse($fechaFin)->endOfDay();

            // Filtrar por rango de fechas en la tabla historico y fecha_desasignacion null
            $flota->whereHas('equipo.historico', function ($query) use ($fechaInicio, $fechaFin) {
                $query->whereBetween('fecha_asignacion', [$fechaInicio, $fechaFin])
                    ->whereNull('fecha_desasignacion');
            });
        }
        if ($ticketPer) {
            $flota->where('ticket_per', 'like', '%' . $ticketPer . '%');
        }
        if ($observaciones) {
            $flota->where('observaciones', 'like', '%' . $observaciones . '%');
        }

        $flota = $flota->orderBy('updated_at', 'desc')->paginate(500);

        foreach ($flota as $f) {
            $ultimoMovimiento = $f->ultimoMovimiento();
            $f->ultimo_movimiento = $ultimoMovimiento ? $ultimoMovimiento->tipoMovimiento->nombre : '-';
            $f->fecha_ultimo_mov = $ultimoMovimiento ? Carbon::parse($ultimoMovimiento->fecha_asignacion)->format('d/m/Y H:i') : '-';
            $f->observaciones_ultimo_mov = $ultimoMovimiento ? $ultimoMovimiento->observaciones : '-';
        }

        return view(
            'flota.busqueda_avanzada',
            compact(
                'flota',
                'texto',
                'equipos',
                'recursos',
                'destinos',
                'tiposTerminal',
                'equipoId',
                'recursoId',
                'destinoId',
                'fechaAsignacion',
                'fechaDesasignacion',
                'fechaRango',
                'ticketPer',
                'observaciones'
            )
        );
    }

    function obtenerNombreMes($mes)
    {
        switch ($mes) {
            case 1:
                return "Enero";
                break;
            case 2:
                return "Febrero";
                break;
            case 3:
                return "Marzo";
                break;
            case 4:
                return "Abril";
                break;
            case 5:
                return "Mayo";
                break;
            case 6:
                return "Junio";
                break;
            case 7:
                return "Julio";
                break;
            case 8:
                return "Agosto";
                break;
            case 9:
                return "Septiembre";
                break;
            case 10:
                return "Octubre";
                break;
            case 11:
                return "Noviembre";
                break;
            case 12:
                return "Diciembre";
                break;
        }
    }

    public function generateDocx($id)
    {
        //dd($id);


        $today = Carbon::now()->toDateTimeString();
        $today = str_replace(' ', '_', $today);
        $today = str_replace(':', '', $today);

        $dia = Carbon::now()->format('d');
        $m = Carbon::now()->format('m');
        $anio = Carbon::now()->format('Y');
        $mes = null;

        switch ($m) {
            case 1:
                $mes = "Enero";
                break;
            case 2:
                $mes = "Febrero";
                break;
            case 3:
                $mes = "Marzo";
                break;
            case 4:
                $mes = "Abril";
                break;
            case 5:
                $mes = "Mayo";
                break;
            case 6:
                $mes = "Junio";
                break;
            case 7:
                $mes = "Julio";
                break;
            case 8:
                $mes = "Agosto";
                break;
            case 9:
                $mes = "Septiembre";
                break;
            case 10:
                $mes = "Octubre";
                break;
            case 11:
                $mes = "Noviembre";
                break;
            case 12:
                $mes = "Diciembre";
                break;
        }


        //dd($anio);

        $rec_de_flota = FlotaGeneral::find($id);

        $phpWord = new PhpWord();

        //$imagenPER = file_get_contents('/img/escudo_per.png');
        $imagenPERStyle = ['width' => 35, 'height' => 35];
        $imagen911Style = ['width' => 35, 'height' => 35];
        $paragraphStyleName = 'pStyle';
        $phpWord->addParagraphStyle($paragraphStyleName, array(
            'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'spacing' => 100
        ));
        $boldFontStyleName = 'BoldText';
        $phpWord->addFontStyle($boldFontStyleName, array(
            'bold' => true,
            'size' => 5
            //'underline' => \PhpOffice\PhpWord\Style\Font::UNDERLINE_SINGLE
        ));


        //$title = 'POLICÍA DE ENTRE RÍOS – DIRECCIÓN OPERACIONES Y SEGURIDAD<w:br/>DIVISIÓN 911 Y VIDEO VIGILANCIA – SECCIÓN TÉCNICA';
        $encabezado = '          POLICÍA DE ENTRE RÍOS – DIRECCIÓN OPERACIONES Y SEGURIDAD          ';
        $titulo = 'RECIBO DE ENTREGA';
        $descripcion = "----------En la ciudad de Paraná, capital de la provincia de Entre Ríos, a los " . $dia . " días del mes de " . $mes . " del año " . $anio . ", siendo las ______ horas, se hace entrega a Personal de " . $rec_de_flota->destino->division->nombre . ", " . $rec_de_flota->equipo->tipo_terminal->marca . ", para ser usado en el Móvil 1141 de Destacamento Tilcara.<w:br/>----------Firmando al pie para constancia y de conformidad.";



        $section = $phpWord->addSection();
        $header = $section->addHeader();
        //$logos = $header->addTable();

        $logoPER = public_path() . '/img/escudo_per.jpg';
        $logo911 = public_path() . '/img/escudo911.jpg';

        $textrun = $header->addTextRun($paragraphStyleName);
        $textrun->addImage($logoPER, $imagenPERStyle);
        $textrun->addText($encabezado, $boldFontStyleName);
        $textrun->addImage($logo911, $imagen911Style);




        //$section->addImage("http://itsolutionstuff.com/frontTheme/images/logo.png");

        $section->addText($descripcion);

        //$objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter = new Word2007($phpWord);

        try {

            $objWriter->save(storage_path($today . 'acta_entrega.docx'));
        } catch (\Exception $e) {
            return response()->json([
                'result' => 'ERROR',
                'message' => $e->getMessage()
            ]);
        }

        //!Deberia guardar el movimiento en el historico cuando se hace un acta de entrega

        return response()->download(storage_path($today . 'acta_entrega.docx'));
    }

    public function generateDocxConTemplate($id)
    {

        $today = Carbon::now()->toDateTimeString();
        $today = str_replace(' ', '_', $today);
        $today = str_replace(':', '', $today);

        $dia = Carbon::now()->format('d');
        $m = Carbon::now()->format('m');
        $anio = Carbon::now()->format('Y');
        $mes = null;

        switch ($m) {
            case 1:
                $mes = "Enero";
                break;
            case 2:
                $mes = "Febrero";
                break;
            case 3:
                $mes = "Marzo";
                break;
            case 4:
                $mes = "Abril";
                break;
            case 5:
                $mes = "Mayo";
                break;
            case 6:
                $mes = "Junio";
                break;
            case 7:
                $mes = "Julio";
                break;
            case 8:
                $mes = "Agosto";
                break;
            case 9:
                $mes = "Septiembre";
                break;
            case 10:
                $mes = "Octubre";
                break;
            case 11:
                $mes = "Noviembre";
                break;
            case 12:
                $mes = "Diciembre";
                break;
        }

        $rec_de_flota = FlotaGeneral::find($id);

        $observaciones = "";
        $destino = $rec_de_flota->destino->nombre . ' dependiente de la ' . $rec_de_flota->destino->dependeDe();
        $cant = '01';
        $marca = $rec_de_flota->equipo->tipo_terminal->marca;
        $modelo = $rec_de_flota->equipo->tipo_terminal->modelo;
        $tei = $rec_de_flota->equipo->tei;
        $issi = $rec_de_flota->equipo->issi;

        $cantNegrita = new TextRun();
        $cantNegrita->addText($cant, array(/*'underline' => 'single', */'size' => 12, 'bold' => true));
        $marcaNegrita = new TextRun();
        $marcaNegrita->addText($marca, array(/*'underline' => 'single', */'size' => 12, 'bold' => true));
        $modeloNegrita = new TextRun();
        $modeloNegrita->addText($modelo, array(/*'underline' => 'single', */'size' => 12, 'bold' => true));
        $teiNegrita = new TextRun();
        $teiNegrita->addText($tei, array(/*'underline' => 'single', */'size' => 12, 'bold' => true));
        $issiNegrita = new TextRun();
        $issiNegrita->addText($issi, array(/*'underline' => 'single', */'size' => 12, 'bold' => true));

        $templateWord = new TemplateProcessor(storage_path("template.docx"));
        $templateWord->setValue('dia', $dia);
        $templateWord->setValue('mes', $mes);
        $templateWord->setValue('anio', $anio);
        $templateWord->setValue('observaciones', $observaciones);
        $templateWord->setValue('destino', $destino);
        $templateWord->setComplexValue('cant', $cantNegrita);
        $templateWord->setComplexValue('marca', $marcaNegrita);
        $templateWord->setComplexValue('modelo', $modeloNegrita);
        $templateWord->setComplexValue('tei', $teiNegrita);
        $templateWord->setComplexValue('issi', $issiNegrita);

        try {
            $templateWord->saveAs(storage_path($today . 'acta_entrega.docx'));
            //$objWriter->save(storage_path($today . 'acta_entrega.docx'));
        } catch (\Exception $e) {
            return response()->json([
                'result' => 'ERROR',
                'message' => $e->getMessage()
            ]);
        }

        //!Deberia guardar el movimiento en el historico cuando se hace un acta de entrega

        return response()->download(storage_path($today . 'acta_entrega.docx'));
    }

    public function generateDocxConTabla($id)
    {

        $today = Carbon::now()->toDateTimeString();
        $today = str_replace(' ', '_', $today);
        $today = str_replace(':', '_', $today);

        $dia = Carbon::now()->format('d');
        $m = Carbon::now()->format('m');
        $anio = Carbon::now()->format('Y');
        $mes = null;

        switch ($m) {
            case 1:
                $mes = "Enero";
                break;
            case 2:
                $mes = "Febrero";
                break;
            case 3:
                $mes = "Marzo";
                break;
            case 4:
                $mes = "Abril";
                break;
            case 5:
                $mes = "Mayo";
                break;
            case 6:
                $mes = "Junio";
                break;
            case 7:
                $mes = "Julio";
                break;
            case 8:
                $mes = "Agosto";
                break;
            case 9:
                $mes = "Septiembre";
                break;
            case 10:
                $mes = "Octubre";
                break;
            case 11:
                $mes = "Noviembre";
                break;
            case 12:
                $mes = "Diciembre";
                break;
        }

        $rec_de_flota = FlotaGeneral::find($id);

        $document = new TemplateProcessor(storage_path("template_tabla.docx"));

        $data1 = array(
            array(
                "num" => "1",
                "tei" => "1930013250",
                "bat1" => "B001123",
                "bat2" => "B001125",
                "cuna" => "C001234",
                "issi" => "1990001",
                "ptt" => "ST0038"
            ),
            array(
                "num" => "2",
                "tei" => "1930013251",
                "bat1" => "B001124",
                "bat2" => "B001125",
                "cuna" => "C001235",
                "issi" => "1990002",
                "ptt" => "ST0039"
            ),
            array(
                "num" => "3",
                "tei" => "1930013252",
                "bat1" => "B001129",
                "bat2" => "B001126",
                "cuna" => "C001236",
                "issi" => "1990003",
                "ptt" => "ST0040"
            ),
        );


        $document->cloneRowAndSetValues("num", $data1);

        //dd($document);

        try {
            $document->saveAs(storage_path($today . 'acta_entrega.docx'));
            //$objWriter->save(storage_path($today . 'acta_entrega.docx'));
        } catch (\Exception $e) {
            return response()->json([
                'result' => 'ERROR',
                'message' => $e->getMessage()
            ]);
        }

        //!Deberia guardar el movimiento en el historico cuando se hace un acta de entrega

        return response()->download(storage_path($today . 'acta_entrega.docx'));
    }

    public function verHistorico($id)
    {
        $desdeEquipo = false;
        $flota = FlotaGeneral::find($id);

        $hist = Historico::where('equipo_id', $flota->equipo->id)
            ->orderBy('fecha_asignacion', 'desc')
            ->get();

        // Decodificar las rutas de las imágenes si están en formato JSON
        foreach ($hist as $h) {
            if ($h->rutas_imagenes) {
                $h->rutas_imagenes = json_decode($h->rutas_imagenes);
            }
        }

        return view('flota.historico', compact('hist', 'flota', 'desdeEquipo'));
    }

    public function update_historico(Request $request, $id)
    {
        //dd($request->all());
        $desdeEquipo = false;
        try {
            DB::beginTransaction();

            $request->validate([
                'observaciones' => 'required',
                'archivo' => 'nullable|mimes:pdf,doc,docx,xlsx,zip,rar|max:2048',
                'nuevas_imagenes.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
            ]);

            $historico = Historico::find($id);
            $historico->observaciones = $request->observaciones;

            // Array para guardar rutas de imágenes
            $rutasImagenes = json_decode($request->input('imagenes_actuales', '[]'));

            // Subir nuevas imágenes
            if ($request->hasFile('nuevas_imagenes')) {
                foreach ($request->file('nuevas_imagenes') as $nuevaImagen) {
                    $ruta = $nuevaImagen->store('', 'anexos');
                    $rutasImagenes[] = 'anexos/' . $ruta;
                }
            }

            // Subir archivo adjunto si existe
            if ($request->hasFile('archivo')) {
                $rutaArchivo = $request->file('archivo')->store('', 'anexos');
                $rutasImagenes[] = 'anexos/' . $rutaArchivo;
            }

            // Actualizar rutas en la base de datos
            $historico->rutas_imagenes = json_encode($rutasImagenes);
            $historico->save();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Error al guardar el histórico: ' . $e->getMessage());
        }

        // Cargar nuevamente los datos para la vista
        $flota = FlotaGeneral::where('equipo_id', $historico->equipo_id)->first();
        $hist = Historico::where('equipo_id', $flota->equipo->id)
            ->orderBy('fecha_asignacion', 'desc')
            ->get();

        // Decodificar las rutas de las imágenes si están en formato JSON
        foreach ($hist as $h) {
            if ($h->rutas_imagenes) {
                $h->rutas_imagenes = json_decode($h->rutas_imagenes);
            }
        }

        return view('flota.historico', compact('hist', 'flota', 'desdeEquipo'));
    }

    public function create()
    {
        //Equipos que no tiene flota asociada
        $equipos = Equipo::doesntHave('flota_general')->get();
        /*$equipos = Equipo::all();
        dd($equipos->count());*/
        $tipos_movimiento = TipoMovimiento::all();
        $dependencias = Destino::all();
        $recursos = Recurso::all();

        //dd($dependencias);
        return view('flota.crear', compact('equipos', 'dependencias', 'recursos', 'tipos_movimiento'));
    }

    public function store(Request $request)
    {
        request()->validate([
            'tipo_movimiento' => 'required',
            'dependencia' => 'required',
            'equipo' => 'required',
            'fecha_asignacion' => 'required',
            'imagen1' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'imagen2' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'imagen3' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'imagen4' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'imagen5' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'imagen6' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'imagen7' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'imagen8' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'imagen9' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'imagen10' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'archivo' => 'nullable|mimes:pdf,doc,docx,xlsx,zip,rar|max:2048',
        ], [
            'required' => 'El campo :attribute es necesario completar.'
        ]);
        $rutasImagenes = [];
        for ($i = 1; $i <= 10; $i++) {
            $inputName = 'imagen' . $i;
            if ($request->hasFile($inputName)) {
                $rutaImagen = $request->file($inputName)->store('', 'anexos');
                $rutasImagenes[] = 'anexos/' . $rutaImagen;
            }
        }

        // Manejo del archivo adjunto
        if ($request->hasFile('archivo')) {
            $rutaArchivo = $request->file('archivo')->store('', 'anexos');
            $rutasImagenes[] = 'anexos/' . $rutaArchivo; // Esto guardará la ruta del archivo en el mismo campo JSON
        }

        $id_tipo_movimiento = $request->tipo_movimiento;
        $tipo_de_mov = TipoMovimiento::where('id', $id_tipo_movimiento)->first();
        //Recursos que permiten multiples equipos y devolver en un array
        $recursos_con_multiples_equipos = Recurso::where('multi_equipos', true)->pluck('id')->toArray();
        //Se obtienen los id de los tipo de movimientos
        $id_mov_patrimonial = TipoMovimiento::where('nombre', 'Movimiento patrimonial')->value('id');
        $id_inst_completa = TipoMovimiento::where('nombre', 'Instalación completa')->value('id');
        //Validar que permita mov patrimoniales solo en recursos que acepten muchos equipos
        if ($tipo_de_mov->id == $id_mov_patrimonial || $tipo_de_mov->id == $id_inst_completa) {
            $f = FlotaGeneral::where('recurso_id', $request->recurso)->first();
            if (!is_null($f) && !in_array($f->recurso_id, $recursos_con_multiples_equipos)) {
                $r = Recurso::find($f->recurso_id);
                $e_asociado_id = FlotaGeneral::where('recurso_id', $r->id)->value('equipo_id');
                $equipo_asociado = Equipo::where('id', $e_asociado_id)->first();
                return back()->with('error', "El recurso '$r->nombre' ya tiene asociado el equipo TEI: " . $equipo_asociado->tei . " ISSI: " . $equipo_asociado->issi);
            }
        }

        try {
            DB::beginTransaction();
            $flota = new FlotaGeneral();
            $historico = new Historico();
            $flota->equipo_id = $request->equipo;
            $flota->recurso_id = $request->recurso;
            $flota->destino_id = $request->dependencia;
            $flota->fecha_asignacion = $request->fecha_asignacion;
            $flota->ticket_per = $request->ticket_per;
            $flota->observaciones = $request->observaciones;
            $flota->save();

            $historico->equipo_id = $request->equipo;
            $historico->recurso_id = $request->recurso;
            $r = Recurso::find($request->recurso);
            if ($r) {
                $v = Vehiculo::find($r->vehiculo_id);
            }
            $historico->recurso_asignado = ($r) ? $r->nombre : null;
            $historico->vehiculo_asignado = ($v) ? $v->dominio : null;

            $historico->destino_id = $request->dependencia;
            $historico->fecha_asignacion = $request->fecha_asignacion;
            $historico->tipo_movimiento_id = $request->tipo_movimiento;
            $historico->ticket_per = $request->ticket_per;
            $historico->rutas_imagenes = json_encode($rutasImagenes);
            $historico->observaciones = $request->observaciones;
            $historico->save();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'result' => 'ERROR',
                'message' => $e->getMessage()
            ]);
        }
        return redirect()->route('flota.index');
    }

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        $flota = FlotaGeneral::find($id);
        $equipos = Equipo::all();
        //Los equipos que se pueden usar para reemplazar son los que estan en stock
        $recurso_stock = Recurso::where('nombre', 'Stock 911')->first();
        //-------------------------------------------------------------------------
        $flotas_stock = FlotaGeneral::with('equipo')->where('recurso_id', $recurso_stock->id)->get();
        $dependencias = Destino::all();
        $recursos = Recurso::all();
        $tipos_movimiento = TipoMovimiento::all();
        $hist = Historico::where('equipo_id', $flota->equipo_id)->orderBy('created_at', 'desc')->first();
        //dd($flotas_stock);

        return view('flota.editar', compact('flota', 'equipos', 'dependencias', 'recursos', 'tipos_movimiento', 'hist', 'flotas_stock'));
    }

    public function update(Request $request, $id)
    {
        //dd($request->all());
        request()->validate([
            'tipo_movimiento' => 'required',
            'equipo' => 'required',
            'fecha_asignacion' => 'required',
            'imagen1' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'imagen2' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'imagen3' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'imagen4' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'imagen5' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'imagen6' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'imagen7' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'imagen8' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'imagen9' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'imagen10' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'archivo' => 'nullable|mimes:pdf,doc,docx,xlsx,zip,rar|max:2048',
        ], [
            'required' => 'El campo :attribute es necesario completar.'
        ]);

        $rutasImagenes = [];
        for ($i = 1; $i <= 10; $i++) {
            $inputName = 'imagen' . $i;
            if ($request->hasFile($inputName)) {
                $rutaImagen = $request->file($inputName)->store('', 'anexos');
                $rutasImagenes[] = 'anexos/' . $rutaImagen;
            }
        }

        // Manejo del archivo adjunto
        if ($request->hasFile('archivo')) {
            $rutaArchivo = $request->file('archivo')->store('', 'anexos');
            $rutasImagenes[] = 'anexos/' . $rutaArchivo; // Esto guardará la ruta del archivo en el mismo campo JSON
        }
        $soloModificaHistorico = (isset($request->solo_modificar_historico)) ? true : false;
        $jsonData = json_decode($request->tipo_movimiento);
        $id_tipo_movimiento = $jsonData->id;
        $flota = FlotaGeneral::find($id);
        $tipo_de_mov = TipoMovimiento::where('id', $id_tipo_movimiento)->first();
        //Recursos que permiten multiples equipos y devolver en un array
        $recursos_con_multiples_equipos = Recurso::where('multi_equipos', true)->pluck('id')->toArray();
        $recurso_stock = Recurso::where('nombre', 'Stock 911')->first();
        $recurso_soporte_pg = Recurso::where('nombre', 'Soporte 1er Nivel - PG')->first();
        $recurso_lote_temporal_pg = Recurso::where('nombre', 'Lote Temporal PG')->first();
        $recurso_equipos_extraviados = Recurso::where('nombre', 'Equipos extraviados')->first();
        //Se obtienen los id de los tipo de movimientos
        $id_mov_patrimonial = TipoMovimiento::where('nombre', 'Movimiento patrimonial')->value('id');
        $id_desinst_completa = TipoMovimiento::where('nombre', 'Desinstalación completa')->value('id');
        $id_inst_completa = TipoMovimiento::where('nombre', 'Instalación completa')->value('id');
        $id_provisorio = TipoMovimiento::where('nombre', 'Provisorio')->value('id');
        $id_revision = TipoMovimiento::where('nombre', 'Revisión')->value('id');
        $id_devolucion = TipoMovimiento::where('nombre', 'Devolución')->value('id');
        $id_reemplazo = TipoMovimiento::where('nombre', 'Reemplazo')->value('id');
        $id_recambio = TipoMovimiento::where('nombre', 'Recambio')->value('id');
        $id_devolver_equipo_temporal = TipoMovimiento::where('nombre', 'Devolver equipo temporal')->value('id');
        $id_devolver_a_dependencia = TipoMovimiento::where('nombre', 'Devolución a dependencia')->value('id');
        $id_baja = TipoMovimiento::where('nombre', 'Baja')->value('id');
        $id_extraviado = TipoMovimiento::where('nombre', 'Extraviado')->value('id');
        $id_recuperado = TipoMovimiento::where('nombre', 'Recuperado')->value('id');

        //Validar que permita mov patrimoniales solo en recursos que acepten muchos equipos
        if ($tipo_de_mov->id == $id_mov_patrimonial || $tipo_de_mov->id == $id_inst_completa) {
            $f = FlotaGeneral::where('recurso_id', $request->recurso)->first();
            if (!is_null($f) && !in_array($f->recurso_id, $recursos_con_multiples_equipos)) {
                $r = Recurso::find($f->recurso_id);
                $e_asociado_id = FlotaGeneral::where('recurso_id', $r->id)->value('equipo_id');
                $equipo_asociado = Equipo::where('id', $e_asociado_id)->first();
                return back()->with('error', "El recurso '$r->nombre' ya tiene asociado el equipo TEI: " . $equipo_asociado->tei . " ISSI: " . $equipo_asociado->issi);
            }
        }

        try {
            DB::beginTransaction();
            if (!is_null($flota)) {
                $historico = new Historico();
                $histAnt = Historico::where('equipo_id', $request->equipo)->orderBy('fecha_asignacion', 'desc')->first();
                if (!is_null($histAnt) && !$soloModificaHistorico) {
                    $histAnt->fecha_desasignacion = $request->fecha_asignacion;
                }
                if (!$soloModificaHistorico) {
                    $flota->equipo_id = $request->equipo;
                    $flota->ticket_per = $request->ticket_per;
                    $flota->ticket_per = $request->ticket_per;
                    $flota->observaciones = $request->observaciones;
                }
                $historico->equipo_id = $request->equipo;
                $historico->ticket_per = $request->ticket_per;
                $historico->observaciones = $request->observaciones;
                $historico->tipo_movimiento_id = $tipo_de_mov->id;
                $historico->fecha_asignacion = $request->fecha_asignacion;
                $historico->rutas_imagenes = json_encode($rutasImagenes);
                if ($tipo_de_mov->id != $id_reemplazo) {
                    $historico->destino_id = $request->dependencia;
                }

                switch ($tipo_de_mov->id) {
                    case $id_mov_patrimonial:
                    case $id_inst_completa:
                        $r = Recurso::find($request->recurso);
                        $v = null;
                        if (!is_null($r)) {
                            $v = Vehiculo::find($r->vehiculo_id);
                        }
                        $historico->recurso_asignado = !is_null($r) ? $r->nombre : null;
                        $historico->vehiculo_asignado = !is_null($v) ? $v->dominio : null;
                        $historico->recurso_desasignado = ($histAnt->recurso_asignado) ? $histAnt->recurso_asignado : null;
                        $historico->vehiculo_desasignado = ($histAnt->vehiculo_asignado) ? $histAnt->vehiculo_asignado : null;
                        if (!$soloModificaHistorico) {
                            $flota->destino_id = $request->dependencia;
                            $flota->recurso_id = $request->recurso;
                        }
                        break;

                    case $id_desinst_completa:
                        if (!$soloModificaHistorico) {
                            $flota->recurso_id = $recurso_stock->id; //asigna al stock
                            $flota->destino_id = $recurso_stock->destino->id;
                        }
                        $historico->recurso_id = $recurso_stock->id; //asigna al stock
                        $historico->recurso_asignado = $recurso_stock->nombre; //asigna al stock;
                        $historico->vehiculo_asignado = null;
                        $historico->recurso_desasignado = ($histAnt->recurso_asignado) ? $histAnt->recurso_asignado : null;
                        $historico->vehiculo_desasignado = ($histAnt->vehiculo_asignado) ? $histAnt->vehiculo_asignado : null;
                        $historico->destino_id = $recurso_stock->destino->id;
                        break;

                    case $id_extraviado:
                        $historico->recurso_id = $recurso_equipos_extraviados->id;
                        $historico->recurso_asignado = $recurso_equipos_extraviados->nombre;
                        $historico->vehiculo_asignado = null;
                        $historico->recurso_desasignado = ($histAnt->recurso_asignado) ? $histAnt->recurso_asignado : null;
                        $historico->vehiculo_desasignado = ($histAnt->vehiculo_asignado) ? $histAnt->vehiculo_asignado : null;
                        $historico->destino_id = $recurso_equipos_extraviados->destino->id;
                        break;

                    case $id_provisorio:
                        $r = Recurso::find($request->recurso);
                        $v = null;
                        if (!is_null($r)) {
                            $v = Vehiculo::find($r->vehiculo_id);
                        }
                        $historico->recurso_asignado = !is_null($r) ? $r->nombre : null;
                        $historico->vehiculo_asignado = !is_null($v) ? $v->dominio : null;
                        if (!$soloModificaHistorico) {
                            $flota->recurso_id = $request->recurso;
                        }
                        break;

                    case $id_devolver_equipo_temporal:
                        if (!$soloModificaHistorico) {
                            $flota->recurso_id = $recurso_lote_temporal_pg->id; //asigna al lote temporal PG
                            $flota->destino_id = $recurso_lote_temporal_pg->destino->id; //asigna al lote temporal PG
                        }
                        $historico->recurso_id = $recurso_lote_temporal_pg->id; //asigna al lote temporal PG
                        $historico->recurso_asignado = $recurso_lote_temporal_pg->nombre; //asigna al lote temporal PG
                        $historico->vehiculo_asignado = null;
                        $historico->recurso_desasignado = ($histAnt->recurso_asignado) ? $histAnt->recurso_asignado : null;
                        $historico->vehiculo_desasignado = ($histAnt->vehiculo_asignado) ? $histAnt->vehiculo_asignado : null;
                        $historico->destino_id = $recurso_lote_temporal_pg->destino->id; //asigna al lote temporal PG
                        break;

                    case $id_devolucion:
                        if (!$soloModificaHistorico) {
                            $flota->recurso_id = $recurso_stock->id; //asigna al stock
                            $flota->destino_id = $recurso_stock->destino->id;
                        }
                        $historico->recurso_id = $recurso_stock->id; //asigna al stock
                        $historico->recurso_asignado = $recurso_stock->nombre; //asigna al stock;
                        $historico->vehiculo_asignado = null;
                        $historico->recurso_desasignado = ($histAnt->recurso_asignado) ? $histAnt->recurso_asignado : null;
                        $historico->vehiculo_desasignado = ($histAnt->vehiculo_asignado) ? $histAnt->vehiculo_asignado : null;
                        $historico->destino_id = $recurso_stock->destino->id;
                        break;

                    case $id_devolver_a_dependencia:
                    case $id_recuperado:
                    case $id_revision:
                        $r = Recurso::find($request->recurso);
                        $v = null;
                        if (!is_null($r)) {
                            $v = Vehiculo::find($r->vehiculo_id);
                        }
                        $historico->recurso_asignado = !is_null($r) ? $r->nombre : null;
                        $historico->vehiculo_asignado = !is_null($v) ? $v->dominio : null;
                        if (!$soloModificaHistorico) {
                            $flota->recurso_id = $request->recurso;
                        }
                        break;

                    case $id_reemplazo:
                        //Nueva flota para asignar el equipo desinstalado a Soporte 1er nivel - PG
                        if (!$soloModificaHistorico) {
                            $flotaReemplazo = FlotaGeneral::where('equipo_id', $request->equipoReemplazo)->first();
                            $flotaReemplazo->equipo_id = $request->equipo;
                            $flotaReemplazo->recurso_id = $recurso_soporte_pg->id;
                            $flotaReemplazo->destino_id = $recurso_soporte_pg->destino->id;
                            $flotaReemplazo->fecha_asignacion = $request->fecha_asignacion;
                            $flotaReemplazo->ticket_per = $request->ticket_per;
                            $flotaReemplazo->observaciones = $request->observaciones;
                            $flota->equipo_id = $request->equipoReemplazo;
                            $flotaReemplazo->save();
                        }

                        $r = Recurso::find($flota->recurso_id);
                        $v = null;
                        if (!is_null($r)) {
                            $v = Vehiculo::find($r->vehiculo_id);
                        }

                        //Historico del nuevo equipo instalado
                        $historicoReemplazo = new Historico();
                        $histAntReemplazo = Historico::where('equipo_id', $request->equipoReemplazo)->orderBy('fecha_asignacion', 'desc')->first();
                        if (!is_null($histAntReemplazo) && !$soloModificaHistorico) {
                            $histAntReemplazo->fecha_desasignacion = $request->fecha_asignacion;
                        }
                        $historicoReemplazo->equipo_id = $request->equipoReemplazo;
                        $historicoReemplazo->ticket_per = $request->ticket_per;
                        $historicoReemplazo->observaciones = $request->observaciones;
                        $historicoReemplazo->tipo_movimiento_id = $tipo_de_mov->id;
                        $historicoReemplazo->fecha_asignacion = $request->fecha_asignacion;
                        $historicoReemplazo->recurso_id = $flota->recurso_id;
                        $historicoReemplazo->recurso_asignado = $flota->recurso->nombre;
                        $historicoReemplazo->vehiculo_asignado = !is_null($v) ? $v->dominio : null;
                        $historicoReemplazo->recurso_desasignado = ($histAntReemplazo->recurso_asignado) ? $histAntReemplazo->recurso_asignado : null;
                        $historicoReemplazo->vehiculo_desasignado = ($histAntReemplazo->vehiculo_asignado) ? $histAntReemplazo->vehiculo_asignado : null;
                        $historicoReemplazo->destino_id = $flota->destino->id;

                        //Historico del equipo que se desinstala
                        $historicoReemplazo->tipo_movimiento_id = $tipo_de_mov->id;
                        $historico->recurso_id = $recurso_soporte_pg->id; //asigna al soporte PG
                        $historico->recurso_asignado = $recurso_soporte_pg->nombre; //asigna al soporte PG;
                        $historico->vehiculo_asignado = null;
                        $historico->recurso_desasignado = ($histAnt->recurso_asignado) ? $histAnt->recurso_asignado : null;
                        $historico->vehiculo_desasignado = ($histAnt->vehiculo_asignado) ? $histAnt->vehiculo_asignado : null;
                        $historico->destino_id = $recurso_soporte_pg->destino->id;

                        $historicoReemplazo->save();
                        $histAntReemplazo->save();

                        break;
                    case $id_recambio:
                        //Nueva flota para asignar el equipo desinstalado a Stock 911
                        if (!$soloModificaHistorico) {
                            $flotaReemplazo = FlotaGeneral::where('equipo_id', $request->equipoReemplazo)->first();
                            $flotaReemplazo->equipo_id = $request->equipo;
                            $flotaReemplazo->recurso_id = $recurso_stock->id;
                            $flotaReemplazo->destino_id = $recurso_stock->destino->id;
                            $flotaReemplazo->fecha_asignacion = $request->fecha_asignacion;
                            $flotaReemplazo->ticket_per = $request->ticket_per;
                            $flotaReemplazo->observaciones = $request->observaciones;
                            $flota->equipo_id = $request->equipoReemplazo;
                            $flotaReemplazo->save();
                        }

                        $r = Recurso::find($flota->recurso_id);
                        $v = null;
                        if (!is_null($r)) {
                            $v = Vehiculo::find($r->vehiculo_id);
                        }

                        //Historico del nuevo equipo instalado
                        $historicoReemplazo = new Historico();
                        $histAntReemplazo = Historico::where('equipo_id', $request->equipoReemplazo)->orderBy('created_at', 'desc')->first();
                        if (!is_null($histAntReemplazo) && !$soloModificaHistorico) {
                            $histAntReemplazo->fecha_desasignacion = $request->fecha_asignacion;
                        }
                        $historicoReemplazo->equipo_id = $request->equipoReemplazo;
                        $historicoReemplazo->ticket_per = $request->ticket_per;
                        $historicoReemplazo->observaciones = $request->observaciones;
                        $historicoReemplazo->tipo_movimiento_id = $tipo_de_mov->id;
                        $historicoReemplazo->fecha_asignacion = $request->fecha_asignacion;
                        $historicoReemplazo->recurso_id = $flota->recurso_id;
                        $historicoReemplazo->recurso_asignado = $flota->recurso->nombre;
                        $historicoReemplazo->vehiculo_asignado = !is_null($v) ? $v->dominio : null;
                        $historicoReemplazo->recurso_desasignado = ($histAntReemplazo->recurso_asignado) ? $histAntReemplazo->recurso_asignado : null;
                        $historicoReemplazo->vehiculo_desasignado = ($histAntReemplazo->vehiculo_asignado) ? $histAntReemplazo->vehiculo_asignado : null;
                        $historicoReemplazo->destino_id = $flota->destino->id;

                        //Historico del equipo que se desinstala
                        $historicoReemplazo->tipo_movimiento_id = $tipo_de_mov->id;
                        $historico->recurso_id = $recurso_stock->id; //asigna al Stock 911
                        $historico->recurso_asignado = $recurso_stock->nombre; //asigna al Stock 911;
                        $historico->vehiculo_asignado = null;
                        $historico->recurso_desasignado = ($histAnt->recurso_asignado) ? $histAnt->recurso_asignado : null;
                        $historico->vehiculo_desasignado = ($histAnt->vehiculo_asignado) ? $histAnt->vehiculo_asignado : null;
                        $historico->destino_id = $recurso_stock->destino->id;

                        $historicoReemplazo->save();
                        $histAntReemplazo->save();

                        break;
                    default:
                        $historico->destino_id = $flota->destino_id;
                        $r = Recurso::find($flota->recurso_id);
                        $v = null;
                        if (!is_null($r)) {
                            $v = Vehiculo::find($r->vehiculo_id);
                        }
                        $historico->recurso_asignado = !is_null($r) ? $r->nombre : null;
                        $historico->vehiculo_asignado = !is_null($v) ? $v->dominio : null;
                        break;
                }
                $histAnt->save();
                $historico->save();
                $flota->save();
                //Cambiar estado al equipo
                $this->cambiarEstadoAlEquipo($request->equipo, $tipo_de_mov->id, $soloModificaHistorico);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'result' => 'ERROR',
                'message' => $e->getMessage()
            ]);
        }
        return redirect()->route('flota.index');
    }

    private function cambiarEstadoAlEquipo($id, $tipo_de_mov_id, $soloModificaHistorico)
    {
        if ($soloModificaHistorico) {
            return;
        }
        try {
            DB::beginTransaction();
            //Obtener ids de estados
            $id_estado_en_revision = Estado::where('nombre', 'En revision')->value('id');
            $id_estado_usado = Estado::where('nombre', 'Usado')->value('id');
            $id_estado_baja = Estado::where('nombre', 'Baja')->value('id');
            $id_estado_perdido = Estado::where('nombre', 'Perdido')->value('id');
            $id_estado_temporal = Estado::where('nombre', 'Temporal')->value('id');
            $id_estado_no_funciona = Estado::where('nombre', 'No funciona')->value('id');
            //Se obtienen los id de los tipo de movimientos
            $id_mov_patrimonial = TipoMovimiento::where('nombre', 'Movimiento patrimonial')->value('id');
            $id_desinst_completa = TipoMovimiento::where('nombre', 'Desinstalación completa')->value('id');
            $id_inst_completa = TipoMovimiento::where('nombre', 'Instalación completa')->value('id');
            $id_provisorio = TipoMovimiento::where('nombre', 'Provisorio')->value('id');
            $id_revision = TipoMovimiento::where('nombre', 'Revisión')->value('id');
            $id_devolucion = TipoMovimiento::where('nombre', 'Devolución')->value('id');
            $id_reemplazo = TipoMovimiento::where('nombre', 'Reemplazo')->value('id');
            $id_recambio = TipoMovimiento::where('nombre', 'Recambio')->value('id');
            $id_devolver_equipo_temporal = TipoMovimiento::where('nombre', 'Devolver equipo temporal')->value('id');
            $id_baja = TipoMovimiento::where('nombre', 'Baja')->value('id');
            $id_alta = TipoMovimiento::where('nombre', 'Alta')->value('id');
            $id_extraviado = TipoMovimiento::where('nombre', 'Extraviado')->value('id');

            $e = Equipo::find($id);
            if ($e) {
                if ($tipo_de_mov_id == $id_revision || $tipo_de_mov_id == $id_reemplazo) {
                    $e->estado_id = $id_estado_en_revision;
                    $e->save();
                } else if ($tipo_de_mov_id == $id_inst_completa || $tipo_de_mov_id == $id_mov_patrimonial || $tipo_de_mov_id == $id_provisorio || $tipo_de_mov_id == $id_recambio) {
                    $e->estado_id = $id_estado_usado;
                    $e->save();
                } else if ($tipo_de_mov_id == $id_baja) {
                    $e->estado_id = $id_estado_baja;
                    $e->save();
                } else if ($tipo_de_mov_id == $id_extraviado) {
                    $e->estado_id = $id_estado_perdido;
                    $e->save();
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'result' => 'ERROR',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function destroy($id)
    {
        $flota = FlotaGeneral::find($id);
        $flota->delete();
        return redirect()->route('flota.index');
    }

    public function getRecursosJSON(Request $request)
    {
        $recursos = Recurso::with('vehiculo')
            ->where('destino_id', $request->destino_id)
            ->orderBy('nombre', 'asc')
            ->get();
        return response()->json($recursos);
    }
}
