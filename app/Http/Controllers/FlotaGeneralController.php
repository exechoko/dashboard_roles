<?php

namespace App\Http\Controllers;

use App\Models\Destino;
use App\Models\Equipo;
use App\Models\FlotaGeneral;
use App\Models\Recurso;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

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
        $flota = FlotaGeneral::where('observaciones', 'LIKE', '%'.$texto.'%')
                    ->orderBy('observaciones','asc')
                    ->paginate(10);

        return view('flota.index', compact('flota', 'texto'));
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
        //
    }

    public function update(Request $request, $id)
    {
        //
    }

    public function destroy($id)
    {
        //
    }
}
