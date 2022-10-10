<?php

namespace App\Http\Controllers;

use App\Models\Destino;
use App\Models\Equipo;
use App\Models\FlotaGeneral;
use App\Models\Recurso;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\TemplateProcessor;
use PhpOffice\PhpWord\Writer\Word2007;

class FlotaGeneralController extends Controller
{
    function __construct(){
        $this->middleware('permission:ver-flota|crear-flota|editar-flota|borrar-flota')->only('index');
        $this->middleware('permission:crear-flota', ['only'=>['create', 'store']]);
        $this->middleware('permission:editar-flota', ['only'=>['edit', 'update']]);
        $this->middleware('permission:borrar-flota', ['only'=>['destroy']]);
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
        })->orderBy('id', 'asc')->get();//->orWhere('observaciones', 'LIKE', '%' . $texto . '%')->orderBy('id', 'asc')->get();

        //dd($flota);

        return view('flota.index', compact('flota', 'texto'));
    }

    function obtenerNombreMes($mes){
        switch ($mes) {
            case 1: return "Enero";
            break;
            case 2: return "Febrero";
            break;
            case 3: return "Marzo";
            break;
            case 4: return "Abril";
            break;
            case 5: return "Mayo";
            break;
            case 6: return "Junio";
            break;
            case 7: return "Julio";
            break;
            case 8: return "Agosto";
            break;
            case 9: return "Septiembre";
            break;
            case 10: return "Octubre";
            break;
            case 11: return "Noviembre";
            break;
            case 12: return "Diciembre";
            break;
        }
    }

    public function generateDocx($id){
        //dd($id);


        $today = Carbon::now()->toDateTimeString();
        $today = str_replace(' ', '_', $today);
        $today = str_replace(':', '', $today);

        $dia = Carbon::now()->format('d');
        $m = Carbon::now()->format('m');
        $anio = Carbon::now()->format('Y');
        $mes = null;

        switch ($m) {
            case 1: $mes = "Enero";
            break;
            case 2: $mes = "Febrero";
            break;
            case 3: $mes = "Marzo";
            break;
            case 4: $mes = "Abril";
            break;
            case 5: $mes = "Mayo";
            break;
            case 6: $mes = "Junio";
            break;
            case 7: $mes = "Julio";
            break;
            case 8: $mes = "Agosto";
            break;
            case 9: $mes = "Septiembre";
            break;
            case 10: $mes = "Octubre";
            break;
            case 11: $mes = "Noviembre";
            break;
            case 12: $mes = "Diciembre";
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
        $descripcion = "----------En la ciudad de Paraná, capital de la provincia de Entre Ríos, a los ". $dia ." días del mes de ". $mes ." del año ". $anio .", siendo las ______ horas, se hace entrega a Personal de ". $rec_de_flota->destino->division->nombre .", " . $rec_de_flota->equipo->tipo_terminal->marca . ", para ser usado en el Móvil 1141 de Destacamento Tilcara.<w:br/>----------Firmando al pie para constancia y de conformidad.";



        $section = $phpWord->addSection();
        $header = $section->addHeader();
        //$logos = $header->addTable();

        $logoPER = public_path().'/img/escudo_per.jpg';
        $logo911 = public_path().'/img/escudo911.jpg';

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

    public function generateDocxConTemplate($id){

        $today = Carbon::now()->toDateTimeString();
        $today = str_replace(' ', '_', $today);
        $today = str_replace(':', '', $today);

        $dia = Carbon::now()->format('d');
        $m = Carbon::now()->format('m');
        $anio = Carbon::now()->format('Y');
        $mes = null;

        switch ($m) {
            case 1: $mes = "Enero";
            break;
            case 2: $mes = "Febrero";
            break;
            case 3: $mes = "Marzo";
            break;
            case 4: $mes = "Abril";
            break;
            case 5: $mes = "Mayo";
            break;
            case 6: $mes = "Junio";
            break;
            case 7: $mes = "Julio";
            break;
            case 8: $mes = "Agosto";
            break;
            case 9: $mes = "Septiembre";
            break;
            case 10: $mes = "Octubre";
            break;
            case 11: $mes = "Noviembre";
            break;
            case 12: $mes = "Diciembre";
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

    public function generateDocxConTabla($id){

        $today = Carbon::now()->toDateTimeString();
        $today = str_replace(' ', '_', $today);
        $today = str_replace(':', '_', $today);

        $dia = Carbon::now()->format('d');
        $m = Carbon::now()->format('m');
        $anio = Carbon::now()->format('Y');
        $mes = null;

        switch ($m) {
            case 1: $mes = "Enero";
            break;
            case 2: $mes = "Febrero";
            break;
            case 3: $mes = "Marzo";
            break;
            case 4: $mes = "Abril";
            break;
            case 5: $mes = "Mayo";
            break;
            case 6: $mes = "Junio";
            break;
            case 7: $mes = "Julio";
            break;
            case 8: $mes = "Agosto";
            break;
            case 9: $mes = "Septiembre";
            break;
            case 10: $mes = "Octubre";
            break;
            case 11: $mes = "Noviembre";
            break;
            case 12: $mes = "Diciembre";
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

    public function create()
    {
        $equipos = Equipo::all();
        $dependencias = Destino::all();
        $recursos = Recurso::all();

        //dd($dependencias);
        return view('flota.crear', compact('equipos', 'dependencias', 'recursos'));
    }

    public function store(Request $request)
    {

        request()->validate([
            'dependencia' => 'required',
            'equipo' => 'required',
        ], [
            'required' => 'El campo :attribute es necesario completar.'
        ]);

        try{
            DB::beginTransaction();
            $flota = new FlotaGeneral();
            $flota->equipo_id = $request->equipo;
            $flota->recurso_id = $request->recurso;
            $flota->destino_id = $request->dependencia;
            $flota->fecha_asignacion = Carbon::now()->toDateTimeString();
            //$flota->fecha_asignacion = Carbon::createFromFormat('d-m-Y H:i:s', $request->fecha)->toDateTimeString();
            $flota->observaciones = $request->observaciones;
            $flota->save();
            DB::commit();
        } catch (\Exception $e){
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
        $dependencias = Destino::all();
        $recursos = Recurso::all();

        //dd($dependencias);
        return view('flota.editar', compact('flota', 'equipos', 'dependencias', 'recursos'));
    }

    public function update(Request $request, $id)
    {
        dd($request);
        request()->validate([
            'dependencia' => 'required',
            'equipo' => 'required',
        ], [
            'required' => 'El campo :attribute es necesario completar.'
        ]);
    }

    public function destroy($id)
    {
        //
    }
}
