<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Equipo;
use App\Models\Estado;
use App\Models\TipoTerminal;
use Illuminate\Support\Arr;

class EquipoController extends Controller
{
    function __construct(){
        $this->middleware('permission:ver-equipo|crear-equipo|editar-equipo|borrar-equipo')->only('index');
        $this->middleware('permission:crear-equipo', ['only'=>['create', 'store']]);
        $this->middleware('permission:editar-equipo', ['only'=>['edit', 'update']]);
        $this->middleware('permission:borrar-equipo', ['only'=>['destroy']]);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //$tipos_terminales = TipoTerminal::all();
        //$estados = Estado::all();
        $equipos = Equipo::paginate(5);
        return view('equipos.index', compact('equipos'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $estados = Estado::pluck('nombre','nombre')->all();
        $marca_terminal = TipoTerminal::pluck('marca', 'marca');
        $modelo_terminal = TipoTerminal::pluck('modelo', 'modelo');

        return view('equipos.crear',compact('estados', 'marca_terminal', 'modelo_terminal'));
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
        request()->validate([
            'marca_terminal' => 'required',
            'modelo_terminal' => 'required',
            'estados' => 'required',
            'issi' => 'required',
            'tei' => 'required'
        ]);

        $terminal_info = TipoTerminal::where('marca', $request->marca_terminal)->where('modelo', $request->modelo_terminal)->first();
        $estado_info = Estado::where('nombre', $request->estados)->first();

        //Equipo::create($request->all());
        if (!is_null($terminal_info) && !is_null($estado_info)){
            $equipo = new Equipo;
            $equipo->tipo_terminal_id = $terminal_info->id;
            $equipo->estado_id = $estado_info->id;
            $equipo->fecha_estado = $request->fecha_estado;
            $equipo->issi = $request->issi;
            $equipo->tei = $request->tei;
            $equipo->propietario = $request->propietario;
            //$equipo->condicion = $request->condicion;
            $equipo->con_garantia = (isset($request->con_garantia)) ? true : false;
            $equipo->fecha_venc_garantia = $request->fecha_venc_garantia;
            $equipo->observaciones = $request->observaciones;

            $equipo->save();

        }

        return redirect()->route('equipos.index');
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
    public function edit(Equipo $equipo)
    {
        $estados = Estado::pluck('nombre','nombre')->all();
        //$tipos_terminales = TipoTerminal::pluck('marca','marca')->all();
        //$marca_terminal = TipoTerminal::pluck('marca', 'marca');
        $marca_terminal = $equipo->tipo_terminal()->pluck('marca')->all();
        $modelo_terminal = $equipo->tipo_terminal()->pluck('modelo')->all();
        //$modelo_terminal = TipoTerminal::pluck('modelo', 'modelo');
        return view('equipos.editar', compact('equipo','marca_terminal', 'modelo_terminal', 'estados'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request,Equipo $equipo)
    {
        //
        request()->validate([
            'estados' => 'required',
            'issi' => 'required',
            'tei' => 'required'
        ]);

        $estado_info = Estado::where('nombre', $request->estados)->first();

        //Equipo::create($request->all());
        if (!is_null($estado_info)){
            $equipo->estado_id = $estado_info->id;
            $equipo->fecha_estado = $request->fecha_estado;
            $equipo->issi = $request->issi;
            $equipo->tei = $request->tei;
            $equipo->propietario = $request->propietario;
            //$equipo->condicion = $request->condicion;
            $equipo->con_garantia = (isset($request->con_garantia)) ? true : false;
            $equipo->fecha_venc_garantia = $request->fecha_venc_garantia;
            $equipo->observaciones = $request->observaciones;

            $equipo->save();

        }
        return redirect()->route('equipos.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Equipo $equipo)
    {
        //
        $equipo->delete();
        return redirect()->route('equipos.index');
    }
}
