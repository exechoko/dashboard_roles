<?php

namespace App\Http\Controllers;

use App\Imports\CamaraImport;
use App\Models\Camara;
use App\Models\Destino;
use App\Models\TipoCamara;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class CamaraController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:ver-camara|crear-camara|editar-camara|borrar-camara')->only('index');
        $this->middleware('permission:crear-camara', ['only' => ['create', 'store']]);
        $this->middleware('permission:editar-camara', ['only' => ['edit', 'update']]);
        $this->middleware('permission:borrar-camara', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        $texto = trim($request->get('texto')); //trim quita espacios vacios
        $camaras = Camara::where('ip', 'LIKE', '%' . $texto . '%')
            ->orWhere('nombre', 'LIKE', '%' . $texto . '%')
            ->orWhere('sitio', 'LIKE', '%' . $texto . '%')
            ->orWhereHas('tipoCamara', function ($query) use ($texto) {
                $query->where('tipo', 'LIKE', '%' . $texto . '%');
            })
            ->orderBy('id', 'asc')
            ->paginate(20);

        //$camaras = Equipo::paginate(5);
        return view('camaras.index', compact('camaras', 'texto'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $tipoCamara = TipoCamara::all();
        $dependencias = Destino::all();
        return view('camaras.crear', compact('tipoCamara', 'dependencias'));
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
            'tipo_camara_id' => 'required|not_in:Selecciona un tipo de cámara',
            'destino_id' => 'required|not_in:Seleccione una dependencia',
        ], [
            'required' => 'El campo :attribute es necesario completar.'
        ]);
        //dd($request->all());
        try {
            DB::beginTransaction();
            $camara = new Camara;
            $camara->ip = $request->ip;
            $camara->tipo_camara_id = $request->tipo_camara_id;
            $camara->nombre = $request->nombre;
            $camara->latitud = $request->latitud;
            $camara->longitud = $request->longitud;
            $camara->sitio = $request->sitio;
            $camara->destino_id = $request->destino_id;
            $camara->inteligencia = $request->inteligencia;
            $camara->nro_serie = $request->nro_serie;
            $camara->fecha_instalacion = $request->fecha_instalacion;
            $camara->etapa = $request->etapa;
            $camara->observaciones = $request->observaciones;
            $camara->save();

            DB::commit();
        } catch (\Exception $e) {
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
        $camara = Camara::find($id);
        $tipoCamara = TipoCamara::all();
        $dependencias = Destino::all();
        return view('camaras.editar', compact('camara', 'tipoCamara', 'dependencias'));
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
        request()->validate([
            'tipo_camara_id' => 'required|not_in:Selecciona un tipo de cámara',
            'destino_id' => 'required|not_in:Seleccione una dependencia',
        ], [
            'required' => 'El campo :attribute es necesario completar.'
        ]);
        //dd($request->all());
        try {
            DB::beginTransaction();
            $camara = Camara::find($id);

            if (!is_null($camara)) {
                $camara->ip = $request->ip;
                $camara->tipo_camara_id = $request->tipo_camara_id;
                $camara->nombre = $request->nombre;
                $camara->latitud = $request->latitud;
                $camara->longitud = $request->longitud;
                $camara->sitio = $request->sitio;
                $camara->destino_id = $request->destino_id;
                $camara->inteligencia = $request->inteligencia;
                $camara->nro_serie = $request->nro_serie;
                $camara->fecha_instalacion = $request->fecha_instalacion;
                $camara->etapa = $request->etapa;
                $camara->observaciones = $request->observaciones;

                $camara->save();
                DB::commit();
            } else {
                return redirect()->back()->with('error', 'Debe seleccionar un estado.');
            }
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'result' => 'ERROR',
                'message' => $e->getMessage()
            ]);
        }
        return redirect()->route('camaras.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $camara = Camara::find($id);
        $camara->delete();
        return redirect()->route('camaras.index');
    }

    public function importExcel(Request $request)
    {
        $file = $request->file('excel_file');
        Excel::import(new CamaraImport, $file);
        /*Excel::import($file, function($reader){
            foreach ($reader->get() as $camara) {
                Camara::create([
                    'nombre' => $camara->nombre,
                    'ip' =>$camara->ip,
                    'tipo' =>$camara->tipo,
                    'inteligencia' =>$camara->inteligencia,
                    'marca' =>$camara->marca,
                    'modelo' =>$camara->modelo,
                    'nro_serie' =>$camara->nro_serie,
                    'etapa' =>$camara->etapa,
                    'sitio' =>$camara->sitio,
                    'latitud' =>$camara->latitud,
                    'longitud' =>$camara->longitud,
                ]);
            }
        });*/

        return redirect()->back()->with('success', 'Los datos se han importado correctamente');
    }
}
