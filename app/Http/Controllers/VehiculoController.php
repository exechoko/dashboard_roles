<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Vehiculo;
use Illuminate\Support\Facades\DB;

class VehiculoController extends Controller
{
    function __construct(){
        $this->middleware('permission:ver-vehiculo|crear-vehiculo|editar-vehiculo|borrar-vehiculo')->only('index');
        $this->middleware('permission:crear-vehiculo', ['only'=>['create', 'store']]);
        $this->middleware('permission:editar-vehiculo', ['only'=>['edit', 'update']]);
        $this->middleware('permission:borrar-vehiculo', ['only'=>['destroy']]);
    }

    public function index(Request $request)
    {
        $texto = trim($request->get('texto')); //trim quita espacios vacios
        $vehiculos = Vehiculo::where('marca', 'LIKE', '%'.$texto.'%')
                    ->orWhere('modelo', 'LIKE', '%'.$texto.'%')
                    ->orWhere('dominio', 'LIKE', '%'.$texto.'%')
                    ->orWhere('propiedad', 'LIKE', '%'.$texto.'%')
                    ->orderBy('marca','asc')
                    ->paginate(10);

        return view('vehiculos.index', compact('vehiculos', 'texto'));
    }

    public function create()
    {
        $tipo_vehiculo = ['Auto', 'Camioneta', 'Camión', 'Moto', 'Helicoptero'];
        return view('vehiculos.crear', compact('tipo_vehiculo'));
    }

    public function store(Request $request)
    {
        request()->validate([
            'marca' => 'required',
            'modelo' => 'required',
            'dominio' => 'required'
        ], [
            'required' => 'El campo :attribute es necesario completar.'
        ]);

        try{
            DB::beginTransaction();
            $vehiculo = new Vehiculo();
            $vehiculo->tipo_vehiculo = $request->tipo_vehiculo;
            $vehiculo->marca = $request->marca;
            $vehiculo->modelo = $request->modelo;
            $vehiculo->nro_chasis = $request->nro_chasis;
            $vehiculo->dominio = $request->dominio;
            $vehiculo->color = $request->color;
            $vehiculo->propiedad = $request->propiedad;
            $vehiculo->detalles = $request->detalles;
            $vehiculo->observaciones = $request->observaciones;
            $vehiculo->save();
            DB::commit();
        } catch (\Exception $e){
            DB::rollback();
            return response()->json([
                'result' => 'ERROR',
                'message' => $e->getMessage()
              ]);
        }
        return redirect()->route('vehiculos.index');
    }

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        $vehiculo = Vehiculo::find($id);
        return view('vehiculos.editar', compact('vehiculo'));
    }

    public function update(Request $request, $id)
    {
        request()->validate([
            'marca' => 'required',
            'modelo' => 'required',
            'dominio' => 'required'
        ], [
            'required' => 'El campo :attribute es necesario completar.'
        ]);

        $vehiculo = Vehiculo::find($id);
        try{
            DB::beginTransaction();
            $vehiculo->tipo_vehiculo = $request->tipo_vehiculo;
            $vehiculo->marca = $request->marca;
            $vehiculo->modelo = $request->modelo;
            $vehiculo->nro_chasis = $request->nro_chasis;
            $vehiculo->dominio = $request->dominio;
            $vehiculo->color = $request->color;
            $vehiculo->propiedad = $request->propiedad;
            $vehiculo->detalles = $request->detalles;
            $vehiculo->observaciones = $request->observaciones;
            $vehiculo->save();
            DB::commit();
        } catch (\Exception $e){
            DB::rollback();
            return response()->json([
                'result' => 'ERROR',
                'message' => $e->getMessage()
              ]);
        }

        return redirect()->route('vehiculos.index');
    }

    public function destroy($id)
    {
        $vehiculo = Vehiculo::find($id);
        $vehiculo->delete();
        return redirect()->route('vehiculos.index');
    }
}
