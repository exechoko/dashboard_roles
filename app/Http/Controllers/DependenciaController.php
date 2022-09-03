<?php

namespace App\Http\Controllers;

use App\Models\Comisaria;
use App\Models\Departamental;
use App\Models\Destacamento;
use App\Models\Destino;
use App\Models\Direccion;
use App\Models\Division;
use App\Models\Seccion;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DependenciaController extends Controller
{
    function __construct(){
        $this->middleware('permission:ver-dependencia|crear-dependencia|editar-dependencia|borrar-dependencia')->only('index');
        $this->middleware('permission:crear-dependencia', ['only'=>['create', 'store']]);
        $this->middleware('permission:editar-dependencia', ['only'=>['edit', 'update']]);
        $this->middleware('permission:borrar-dependencia', ['only'=>['destroy']]);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $dependencias = Destino::latest()->take(5)->get();
        //dd($dependencias);
        $direcciones = Direccion::all();
        $departamentales = Departamental::all();
        //dd($dependencias);
        return view('dependencias.index', compact('dependencias', 'direcciones', 'departamentales'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $direcciones = Direccion::all();
        $departamentales = Departamental::all();
        //$comisarias = Comisaria::all();

        return view('dependencias.crear',compact('direcciones', 'departamentales'));
    }

    public function getDepartamentales(Request $request){
        $departamentales = Departamental::where('direccion_id', $request->direccion_id)->get();
        return response()->json($departamentales);
    }

    public function getDivisiones(Request $request){
        if(!is_null($request->departamental_id)){
            $divisiones = Division::where('departamental_id', $request->departamental_id)->get();
        } else {

            $divisiones = Division::where('direccion_id', $request->direccion_id)->get();
        }
        return response()->json($divisiones);
    }

    public function getComisarias(Request $request){
        $comisarias = Comisaria::where('departamental_id', $request->departamental_id)->get();
        return response()->json($comisarias);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        request()->validate([
            'direccion' => 'required',
            'nombre' => 'required',
            'tipoDependencia' => 'required',
        ], [
            'required' => 'El campo :attribute es necesario completar.'
        ]);

        $tipoDependencia = $request->tipoDependencia;
        $dependencia = null;
        try{
            DB::beginTransaction();
            $destino = new Destino();
            if($tipoDependencia == 'seccion'){
                $dependencia = new Seccion();
                $dependencia->direccion_id = $request->direccion;
                $dependencia->departamental_id = $request->departamental;
                $dependencia->comisaria_id = $request->comisaria;
                $dependencia->division_id = $request->division;
                $dependencia->nombre ='Sección ' . $request->nombre;
                $dependencia->telefono = $request->nombre;
                $dependencia->ubicacion = $request->nombre;
                //$dependencia->observaciones = $request->nombre;
            } else {
                $dependencia = new Destacamento();
                $dependencia->comisaria_id = $request->comisaria;
                $dependencia->division_id = $request->division;
                $dependencia->nombre = 'Destacamento ' . $request->nombre;
                $dependencia->telefono = $request->nombre;
                $dependencia->ubicacion = $request->nombre;
                //$dependencia->observaciones = $request->nombre;
            }
            $dependencia->save();
            $destino->direccion_id = $request->direccion;
            $destino->departamental_id = $request->departamental;
            $destino->seccion_id = ($tipoDependencia == 'seccion') ? $dependencia->id : null;
            $destino->destacamento_id = ($tipoDependencia == 'destacamento') ? $dependencia->id : null;
            $destino->comisaria_id = $request->comisaria;
            $destino->division_id = $request->division;
            $destino->nombre = $dependencia->nombre;
            $destino->save();
            DB::commit();
        } catch (\Exception $e){
            DB::rollback();
            return response()->json([
                'result' => 'ERROR',
                'message' => $e->getMessage()
              ]);
        }

        return redirect()->route('dependencias.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $destino = null;
        try {
            DB::beginTransaction();
            if($request->tipo_dependencia == 'direccion'){
                $d = Direccion::find($id);
                $destino = Destino::where('direccion_id', $d->id)->where('nombre', $d->nombre)->first();
            } elseif($request->tipo_dependencia == 'departamental'){
                $d = Departamental::find($id);
                $destino = Destino::where('departamental_id', $d->id)->where('nombre', $d->nombre)->first();
            }

            $d->nombre = $request->nombre;
            $destino->nombre = $request->nombre;

            $d->telefono = $request->telefono;
            $destino->telefono = $request->telefono;

            $d->ubicacion = $request->ubicacion;
            $destino->ubicacion = $request->ubicacion;

            $d->save();
            $destino->save();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'result' => 'ERROR',
                'message' => $e->getMessage()
              ]);
        }

        return redirect()->route('dependencias.index');
        //dd($request);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
