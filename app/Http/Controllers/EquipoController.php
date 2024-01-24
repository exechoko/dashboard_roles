<?php

namespace App\Http\Controllers;

use App\Exports\EquiposExport;
use App\Models\Accesorio;
use Illuminate\Http\Request;
use App\Models\Equipo;
use App\Models\Estado;
use App\Models\FlotaGeneral;
use App\Models\TipoTerminal;
use App\Models\Historico;
use App\Models\TipoUso;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class EquipoController extends Controller
{
    function __construct(){
        $this->middleware('permission:ver-equipo|crear-equipo|editar-equipo|borrar-equipo')->only('index');
        $this->middleware('permission:crear-equipo', ['only'=>['create', 'store']]);
        $this->middleware('permission:editar-equipo', ['only'=>['edit', 'update']]);
        $this->middleware('permission:borrar-equipo', ['only'=>['destroy']]);
    }

    public function index(Request $request)
    {
        $texto = trim($request->get('texto')); //trim quita espacios vacios
        $equipos = Equipo::where('tei', 'LIKE', '%'.$texto.'%')
                    ->orWhere('issi', 'LIKE', '%'.$texto.'%')
                    ->orWhere('provisto', 'LIKE', '%'.$texto.'%')
                    ->orWhere('propietario', 'LIKE', '%'.$texto.'%')
                    ->orderBy('tei','asc')
                    ->paginate(10);

        //$equipos = Equipo::paginate(5);
        return view('equipos.index', compact('equipos', 'texto'));
    }

    /*public function busqueda(Request $request)
    {
        //dd($request);
        $input = $request->all();

        if ($request->get('busqueda')) {
            $equipo = Equipo::where("tei", "LIKE", "%{$request->get('busqueda')}%")->paginate(5);
            return view('equipos.index', compact('equipo'));
        }

        return response('equipos.index');
    }*/


    public function create()
    {
        //$uso = TipoUso::pluck('uso', 'uso');
        $estados = Estado::pluck('nombre','nombre')->all();
        $marca_terminal = TipoTerminal::pluck('marca', 'marca');
        $modelo_terminal = TipoTerminal::pluck('modelo', 'modelo');
        $terminales = TipoTerminal::all();

        return view('equipos.crear',compact('estados', 'marca_terminal', 'modelo_terminal', 'terminales'));
    }


    public function store(Request $request)
    {
        //
        request()->validate([
            'terminal' => 'required',
            'estados' => 'required',
            //'issi' => 'required', //No es requerido porque si es un equipo nuevo no tiene asignado uno
            'tei' => 'required'
        ], [
            'required' => 'El campo :attribute es necesario completar.'
        ]);

        //Para no guardar el mismo equipo 2 veces
        $e = Equipo::where('tei', $request->tei)->first();
        if (!is_null($e)){
            return back()->with('error', 'Ya se encuentra un equipo con el mismo TEI');//->withInput();
        }

        $terminal_info = TipoTerminal::find($request->terminal);
        $estado_info = Estado::where('nombre', $request->estados)->first();

        try{
            DB::beginTransaction();
            if (!is_null($terminal_info) && !is_null($estado_info)){
                $equipo = new Equipo;
                $equipo->tipo_terminal_id = $terminal_info->id;
                $equipo->estado_id = $estado_info->id;
                $equipo->fecha_estado = Carbon::now();
                $equipo->issi = $request->issi;
                $equipo->nombre_issi = $request->nombre_issi;
                $equipo->tei = $request->tei;
                $equipo->propietario = $request->propietario;
                $equipo->provisto = $request->provisto;
                $equipo->con_garantia = isset($request->con_garantia);
                $equipo->fecha_venc_garantia = $request->fecha_venc_garantia;
                $equipo->observaciones = $request->observaciones;
                $equipo->save();
            }
            DB::commit();
        } catch (\Exception $e){
            DB::rollback();
            return response()->json([
                'result' => 'ERROR',
                'message' => $e->getMessage()
              ]);
        }

        return redirect()->route('equipos.index');
    }


    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        $equipo = Equipo::find($id);
        $estados = Estado::all();
        $marca_terminal = $equipo->tipo_terminal()->pluck('marca')->all();
        $modelo_terminal = $equipo->tipo_terminal()->pluck('modelo')->all();
        return view('equipos.editar', compact('equipo','marca_terminal', 'modelo_terminal', 'estados'));
    }

    public function update(Request $request,$id)
    {
        //dd($request->all());
        $equipo = Equipo::find($id);

        //$estado_info = Estado::where('nombre', $request->estados)->first();
        //dd($estado_info->id);

        request()->validate([
            //'estados' => 'required',
            //'issi' => 'required', //No es requerido porque si es un equipo nuevo no tiene asignado uno
            'tei' => 'required'
        ], [
            'required' => 'El campo :attribute es necesario completar.'
        ]);

        //if (!is_null($estado_info)){
            $equipo->estado_id = $request->estado;
            $equipo->fecha_estado = $request->fecha_estado;
            $equipo->issi = $request->issi;
            $equipo->tei = $request->tei;
            $equipo->nombre_issi = $request->nombre_issi;
            /*$equipo->gps = (isset($request->gps)) ? true : false;
            $equipo->frente_remoto = (isset($request->frente_remoto)) ? true : false;
            $equipo->rf = (isset($request->rf)) ? true : false;
            $equipo->kit_inst = (isset($request->kit_inst)) ? true : false;
            $equipo->operativo = (isset($request->operativo)) ? true : false;
            $equipo->desc_gps = $request->desc_gps;
            $equipo->desc_frente = $request->desc_frente;
            $equipo->desc_rf = $request->desc_rf;
            $equipo->desc_kit_inst = $request->desc_kit_inst;*/
            $equipo->provisto = $request->provisto;
            $equipo->propietario = $request->propietario;
            $equipo->con_garantia = (isset($request->con_garantia)) ? true : false;
            $equipo->fecha_venc_garantia = $request->fecha_venc_garantia;
            $equipo->observaciones = $request->observaciones;

            $equipo->save();
        //} else {
        //    return redirect()->back()->with('error', 'Debe seleccionar un estado.');
        //}
        return redirect()->route('equipos.index');
    }

    public function destroy($id)
    {
        $equipo = Equipo::find($id);
        if (!$equipo) {
            return redirect()->route('equipos.index')->with('error', 'Equipo no encontrado.');
        }
        $equipo->flota_general()->delete(); // Esto eliminará los registros relacionados en FlotaGeneral
        $equipo->historico()->delete(); // Esto eliminará los registros relacionados en Historico
        $equipo->delete(); // Finalmente, eliminar el equipo
        return redirect()->route('equipos.index')->with('success', 'Equipo y registros relacionados eliminados exitosamente.');
    }

    public function verHistoricoDesdeEquipo($id)
    {
        $desdeEquipo = true;
        $flota = Equipo::find($id);

        $hist = Historico::where('equipo_id', $flota->id)->orderBy('fecha_asignacion', 'desc')->get();
        //dd($hist);

        return view('flota.historico', compact('hist', 'flota', 'desdeEquipo'));
    }

    public function exportExcel()
    {
        return Excel::download(new EquiposExport, 'ListadoEquipos_' . Carbon::now() . '.xlsx');
    }
}
