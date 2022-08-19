<?php

namespace App\Http\Controllers;

use App\Models\Departamental;
use App\Models\Destino;
use App\Models\Direccion;
use Illuminate\Http\Request;

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
        $dependencias = Destino::all();
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
