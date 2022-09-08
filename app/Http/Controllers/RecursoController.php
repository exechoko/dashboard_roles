<?php

namespace App\Http\Controllers;

use App\Models\Recurso;
use Illuminate\Http\Request;
use App\Models\Destino;
use App\Models\Vehiculo;

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
        //
    }

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        //
    }

    public function update(Request $request, $id)
    {
        //
    }

    public function destroy($id)
    {
        //
    }
}
