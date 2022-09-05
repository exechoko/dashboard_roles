<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Vehiculo;

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
                    ->orWhere('propietario', 'LIKE', '%'.$texto.'%')
                    ->orderBy('marca','asc')
                    ->paginate(10);

        return view('vehiculos.index', compact('vehiculos', 'texto'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
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
