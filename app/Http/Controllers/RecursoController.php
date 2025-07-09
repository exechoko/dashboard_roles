<?php

namespace App\Http\Controllers;

use App\Models\Recurso;
use Illuminate\Http\Request;
use App\Models\Destino;
use App\Models\Vehiculo;
use Illuminate\Support\Facades\DB;

class RecursoController extends Controller
{
    function __construct(){
        $this->middleware('permission:ver-recurso|crear-recurso|editar-recurso|borrar-recurso')->only('index');
        $this->middleware('permission:crear-recurso', ['only'=>['create', 'store']]);
        $this->middleware('permission:editar-recurso', ['only'=>['edit', 'update']]);
        $this->middleware('permission:borrar-recurso', ['only'=>['destroy']]);
    }

    public function index(Request $request)
    {
        $texto = trim($request->get('texto')); //trim quita espacios vacios
        $dependencia_seleccionada = $request->get('dependencia_id'); // Obtener el ID de la dependencia seleccionada

        $query = Recurso::query();

        // Filtrar por nombre de recurso si se proporciona un texto
        if (!empty($texto)) {
            $query->where('nombre', 'LIKE', '%'.$texto.'%');
        }

        // Filtrar por dependencia si se selecciona una
        if (!empty($dependencia_seleccionada)) {
            $query->where('destino_id', $dependencia_seleccionada);
        }

        $recursos = $query->orderBy('nombre','asc')->paginate(100);

        $dependencias = Destino::all(); // Obtener todas las dependencias para el dropdown

        return view('recursos.index', compact('recursos', 'texto', 'dependencias', 'dependencia_seleccionada'));
    }

    public function create()
    {
        $dependencias = Destino::all();
        $vehiculos = Vehiculo::all();

        //dd($dependencias);
        return view('recursos.crear', compact('dependencias', 'vehiculos'));
    }

    public function store(Request $request)
    {
        //dd($request);
        request()->validate([
            'dependencia' => 'required',
            'nombre' => 'required',
        ], [
            'required' => 'El campo :attribute es necesario completar.'
        ]);

        //Para no guardar el mismo equipo 2 veces
        $r = Recurso::where('nombre', $request->nombre)->first();
        if (!is_null($r)){
            return back()->with('error', 'Ya se encuentra un recurso con el mismo nombre');//->withInput();
        }

        try{
            DB::beginTransaction();
            $recurso = new Recurso();
            $recurso->vehiculo_id = $request->vehiculo;
            $recurso->destino_id = $request->dependencia;
            $recurso->nombre = $request->nombre;
            $recurso->multi_equipos = (isset($request->multi_equipos)) ? true : false;
            $recurso->observaciones = $request->observaciones;
            $recurso->save();
            DB::commit();
        } catch (\Exception $e){
            DB::rollback();
            return response()->json([
                'result' => 'ERROR',
                'message' => $e->getMessage()
              ]);
        }
        return redirect()->route('recursos.index');
    }

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        $recurso = Recurso::find($id);
        $dependencias = Destino::all();
        $vehiculos = Vehiculo::all();

        return view('recursos.editar', compact('recurso', 'dependencias', 'vehiculos'));
    }

    public function update(Request $request, $id)
    {
        //dd($request->all());
        request()->validate([
            'dependencia' => 'required',
            'nombre' => 'required',
        ], [
            'required' => 'El campo :attribute es necesario completar.'
        ]);

        //dd($request->all());

        $recurso = Recurso::find($id);
        try{
            DB::beginTransaction();
            $recurso->vehiculo_id = $request->vehiculo;
            $recurso->destino_id = $request->dependencia;
            $recurso->nombre = $request->nombre;
            $recurso->multi_equipos = (isset($request->multi_equipos)) ? true : false;
            $recurso->observaciones = $request->observaciones;
            $recurso->save();
            DB::commit();
        } catch (\Exception $e){
            DB::rollback();
            return response()->json([
                'result' => 'ERROR',
                'message' => $e->getMessage()
              ]);
        }

        return redirect()->route('recursos.index');
        //return view('recursos.index');

    }

    public function destroy($id)
    {
        $recurso = Recurso::find($id);
        if (!$recurso) {
            return redirect()->route('recursos.index')->with('error', 'Equipo no encontrado.');
        }
        $recurso->flota_general()->delete(); // Esto eliminará los registros relacionados en FlotaGeneral
        $recurso->historico()->delete(); // Esto eliminará los registros relacionados en Historico
        $recurso->delete(); // Finalmente, eliminar el recurso

        return redirect()->route('recursos.index');
    }
}
