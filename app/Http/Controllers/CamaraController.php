<?php

namespace App\Http\Controllers;

use App\Models\Camara;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CamaraController extends Controller
{
    function __construct(){
        $this->middleware('permission:ver-camara|crear-camara|editar-camara|borrar-camara')->only('index');
        $this->middleware('permission:crear-camara', ['only'=>['create', 'store']]);
        $this->middleware('permission:editar-camara', ['only'=>['edit', 'update']]);
        $this->middleware('permission:borrar-camara', ['only'=>['destroy']]);
    }

    public function index(Request $request)
    {
        $texto = trim($request->get('texto')); //trim quita espacios vacios
        $camaras = Camara::where('ip', 'LIKE', '%'.$texto.'%')
                    ->orWhere('nombre', 'LIKE', '%'.$texto.'%')
                    ->orWhere('sitio', 'LIKE', '%'.$texto.'%')
                    ->orderBy('id','asc')
                    ->paginate(10);

        //$equipos = Equipo::paginate(5);
        return view('camaras.index', compact('camaras', 'texto'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('camaras.crear');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //dd($request->all());
        try{
            DB::beginTransaction();
            $camara = new Camara;
            $camara->ip = $request->ip;
            $camara->nombre = $request->nombre;
            $camara->latitud = $request->latitud;
            $camara->longitud = $request->longitud;
            $camara->sitio = $request->sitio;
            $camara->tipo = $request->tipo;
            $camara->inteligencia = $request->inteligencia;
            $camara->observaciones = $request->observaciones;
            $camara->save();

            DB::commit();
        } catch (\Exception $e){
            DB::rollback();
            return response()->json([
                'result' => 'ERROR',
                'message' => $e->getMessage()
              ]);
        }

        return redirect()->route('camaras.index');
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
        //
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
