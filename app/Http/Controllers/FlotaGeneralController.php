<?php

namespace App\Http\Controllers;

use App\Models\Destino;
use App\Models\Equipo;
use App\Models\FlotaGeneral;
use App\Models\Recurso;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpWord\PhpWord;
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

    public function generateDocx($id){
        //dd($id);


        $today = Carbon::now()->toDateTimeString();
        $today = str_replace(' ', '_', $today);
        $today = str_replace(':', '', $today);
        //dd($today);

        $rec_de_flota = FlotaGeneral::find($id);

        $phpWord = new PhpWord();

        $section = $phpWord->addSection();

        $description = "Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod " . $rec_de_flota->equipo->issi . "

tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam,

quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo

consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse

cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non

proident, sunt in culpa qui officia deserunt mollit anim id est laborum.";


        $section->addImage("http://itsolutionstuff.com/frontTheme/images/logo.png");

        $section->addText($description);

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
