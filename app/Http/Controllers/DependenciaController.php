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
        $divisiones = Division::all();
        $comisarias = Comisaria::all();
        $secciones = Seccion::all();
        $destacamentos = Destacamento::all();
        //dd($dependencias);
        return view('dependencias.index', compact('dependencias', 'direcciones', 'departamentales', 'divisiones', 'comisarias', 'secciones', 'destacamentos'));
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
            //'direccion' => 'required',
            'nombre' => 'required',
            'tipoDependencia' => 'required',
        ], [
            'required' => 'El campo :attribute es necesario completar.'
        ]);


        if (is_null($request->direccion) && is_null($request->departamental)){
            return back()->with('error', 'Debe elegir una Dirección o Departamental');//->withInput();
        }

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
                $dependencia->telefono = $request->telefono;
                $dependencia->ubicacion = $request->ubicacion;
                //$dependencia->observaciones = $request->nombre;
            } else {
                $dependencia = new Destacamento();
                $dependencia->departamental_id = $request->departamental;
                $dependencia->comisaria_id = $request->comisaria;
                $dependencia->division_id = $request->division;
                $dependencia->nombre = 'Destacamento ' . $request->nombre;
                $dependencia->telefono = $request->ubicacion;
                $dependencia->ubicacion = $request->ubicacion;
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
        //dd($request->all());
        $destino = null;
        try {
            DB::beginTransaction();
            if($request->tipo_dependencia == 'direccion'){
                $d = Direccion::find($id);
                $destino = Destino::where('direccion_id', $d->id)->where('nombre', $d->nombre)->first();
            } elseif($request->tipo_dependencia == 'departamental'){
                $d = Departamental::find($id);
                $destino = Destino::where('departamental_id', $d->id)->where('nombre', $d->nombre)->first();
            } elseif($request->tipo_dependencia == 'division'){
                $d = Division::find($id);
                $destino = Destino::where('division_id', $d->id)->where('nombre', $d->nombre)->first();
            } elseif($request->tipo_dependencia == 'comisaria'){
                $d = Comisaria::find($id);
                $destino = Destino::where('comisaria_id', $d->id)->where('nombre', $d->nombre)->first();
            } elseif($request->tipo_dependencia == 'seccion'){
                $d = Seccion::find($id);
                $destino = Destino::where('seccion_id', $d->id)->where('nombre', $d->nombre)->first();
            } elseif($request->tipo_dependencia == 'destacamento'){
                $d = Destacamento::find($id);
                $destino = Destino::where('destacamento_id', $d->id)->where('nombre', $d->nombre)->first();
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

    public function showMap()
    {
        // Crear un array con algunas ubicaciones
        /*$ubicaciones = array(
            array(
                'latitud' => -31.7297377,
                'longitud' => -60.5353802,
                'nombre' => 'Cria. 1°',
            ),
            array(
                'latitud' => -31.7370856,
                'longitud' => -60.5298358,
                'nombre' => 'Cria. 2°',
            ),
            array(
                'latitud' => -31.7573272,
                'longitud' => -60.4970351,
                'nombre' => 'Cria. 3°',
            ),
        );*/
        $markers = [
            [
                'latitud' => -31.72978,
                'longitud' => -60.53547,
                'titulo' => 'Cria. 1°',
                'numero' => 1
            ],
            [
                'latitud' => -31.73735,
                'longitud' => -60.5284,
                'titulo' => 'Cria. 2°',
                'numero' => 2
            ],
            [
                'latitud' => -31.757298,
                'longitud' => -60.495857,
                'titulo' => 'Cria. 3°',
                'numero' => 3
            ],
            [
                'latitud' => -31.73771,
                'longitud' => -60.51383,
                'titulo' => 'Cria. 4°',
                'numero' => 4
            ],
            [
                'latitud' => -31.73001,
                'longitud' => -60.54851,
                'titulo' => 'Cria. 5°',
                'numero' => 5
            ],
            [
                'latitud' => -31.74674,
                'longitud' => -60.5364,
                'titulo' => 'Cria. 6°',
                'numero' => 6
            ],
            [
                'latitud' => -31.73711,
                'longitud' => -60.45818,
                'titulo' => 'Cria. 7°',
                'numero' => 7
            ],
            [
                'latitud' => -31.72208,
                'longitud' => -60.51665,
                'titulo' => 'Cria. 8°',
                'numero' => 8
            ],
            [
                'latitud' => -31.74051,
                'longitud' => -60.55312,
                'titulo' => 'Cria. 9°',
                'numero' => 9
            ],
            [
                'latitud' => -31.75655,
                'longitud' => -60.51133,
                'titulo' => 'Cria. 10°',
                'numero' => 10
            ],
            [
                'latitud' => -31.70670,
                'longitud' => -60.56671,
                'titulo' => 'Cria. 11°',
                'numero' => 11
            ],
            [
                'latitud' => -31.75109,
                'longitud' => -60.48563,
                'titulo' => 'Cria. 12°',
                'numero' => 12
            ],
            [
                'latitud' => -31.77106,
                'longitud' => -60.52482,
                'titulo' => 'Cria. 13°',
                'numero' => 13
            ],
            [
                'latitud' => -31.73017,
                'longitud' => -60.49726,
                'titulo' => 'Cria. 14°',
                'numero' => 14
            ],
            [
                'latitud' => -31.77032,
                'longitud' => -60.48219,
                'titulo' => 'Cria. 15°',
                'numero' => 15
            ],
            [
                'latitud' => -31.73434,
                'longitud' => -60.55248,
                'titulo' => 'Cria. 16°',
                'numero' => 16
            ],
            [
                'latitud' => -31.72189,
                'longitud' => -60.54260,
                'titulo' => 'Cria. 17°',
                'numero' => 17
            ]
        ];

        // Convertir el array en formato JSON
        //$jsonUbicaciones = json_encode($ubicaciones);


        return view('dependencias.mapa', ['markers' => $markers]);
    }
}
