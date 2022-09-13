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
        $recursos = Recurso::where('nombre', 'LIKE', '%'.$texto.'%')
                    ->orderBy('nombre','asc')
                    ->paginate(10);

        return view('recursos.index', compact('recursos', 'texto'));
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

        try{
            DB::beginTransaction();
            $recurso = new Recurso();
            $recurso->vehiculo_id = $request->vehiculo;
            $recurso->destino_id = $request->dependencia;
            $recurso->nombre = $request->nombre;
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
        dd($request);
        request()->validate([
            'dependencia' => 'required',
            'nombre' => 'required',
        ], [
            'required' => 'El campo :attribute es necesario completar.'
        ]);

        $recurso = Recurso::find($id);
        try{
            DB::beginTransaction();
            $recurso->vehiculo_id = $request->vehiculo;
            $recurso->destino_id = $request->dependencia;
            $recurso->nombre = $request->nombre;
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

        return view('recursos.index');

    }

    public function destroy($id)
    {
        $recurso = Recurso::find($id);
        $recurso->delete();
        return redirect()->route('recursos.index');
    }
}
