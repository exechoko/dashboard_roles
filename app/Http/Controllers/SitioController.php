<?php

namespace App\Http\Controllers;

use App\Models\Destino;
use App\Models\Sitio;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class SitioController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:ver-sitio|crear-sitio|editar-sitio|borrar-sitio')->only('index');
        $this->middleware('permission:crear-sitio', ['only' => ['create', 'store']]);
        $this->middleware('permission:editar-sitio', ['only' => ['edit', 'update']]);
        $this->middleware('permission:borrar-sitio', ['only' => ['destroy']]);
    }

    public function index()
    {
        $sitios = Sitio::paginate(50);
        return view('sitio.index', compact('sitios'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $dependencias = Destino::all();
        $localidades = [
            'Paraná',
            'Colonia Avellaneda',
            'Oro Verde',
            'San Benito'
        ];
        return view('sitio.crear', compact('dependencias', 'localidades'));
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
        request()->validate([
            'nombre' => 'required',
            'localidad' => 'required|not_in:Seleccionar localidad',
            'destino_id' => 'required|not_in:Seleccionar la dependencia',
        ], [
            'required' => 'El campo :attribute es necesario completar.'
        ]);
        try {
            DB::beginTransaction();
            $sitio = new Sitio;
            $sitio->nombre = $request->nombre;
            $sitio->latitud = $request->latitud;
            $sitio->longitud = $request->longitud;
            $sitio->localidad = $request->localidad;
            $sitio->destino_id = $request->destino_id;
            $sitio->observaciones = $request->observaciones;
            $sitio->save();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'result' => 'ERROR',
                'message' => $e->getMessage()
            ]);
        }

        return redirect()->route('sitios.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Sitio  $sitio
     * @return \Illuminate\Http\Response
     */
    public function show(Sitio $sitio)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Sitio  $sitio
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $sitio = Sitio::find($id);
        $dependencias = Destino::all();
        $localidades = [
            'Paraná',
            'Colonia Avellaneda',
            'Oro Verde',
            'San Benito'
        ];
        return view('sitio.editar', compact('dependencias', 'localidades', 'sitio'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Sitio  $sitio
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Sitio $sitio)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Sitio  $sitio
     * @return \Illuminate\Http\Response
     */
    public function destroy(Sitio $sitio)
    {
        //
    }
}
