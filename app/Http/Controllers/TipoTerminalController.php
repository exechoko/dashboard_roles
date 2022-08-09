<?php

namespace App\Http\Controllers;

use App\Models\Equipo;
use App\Models\TipoTerminal;
use App\Models\TipoUso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TipoTerminalController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $terminales = TipoTerminal::paginate(5);
        return view('terminales.index', compact('terminales'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $tipo_uso = TipoUso::pluck('uso', 'uso');
        /*$marca_terminal = TipoTerminal::pluck('marca', 'marca');
        $modelo_terminal = TipoTerminal::pluck('modelo', 'modelo');*/

        return view('terminales.crear', compact('tipo_uso'));
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
            'tipo_uso' => 'required|not_in:Selecciona su uso',
            'marca' => 'required',
            'modelo' => 'required'
        ], [
            'required' => 'El campo :attribute es necesario completar.'
        ]);

        $uso = TipoUso::where('uso', $request->tipo_uso)->first();

        try {
            DB::beginTransaction();

            $terminal = new TipoTerminal();
            $terminal->tipo_uso_id = $uso->id;
            $terminal->marca = $request->marca;
            $terminal->modelo = $request->modelo;
            $terminal->imagen = $request->imagen;

            $terminal->save();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'result' => 'ERROR',
                'message' => $e->getMessage()
              ]);
        }

        return redirect()->route('terminales.index');
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
        $terminal = TipoTerminal::find($id);
        $tipo_uso = TipoUso::pluck('uso','uso')->all();
        return view('terminales.editar', compact('terminal','tipo_uso'));
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
            'tipo_uso' => 'required|not_in:Selecciona su uso',
            'marca' => 'required',
            'modelo' => 'required'
        ], [
            'required' => 'El campo :attribute es necesario completar.'
        ]);

        $uso = TipoUso::where('uso', $request->tipo_uso)->first();
        $terminal = TipoTerminal::find($id);

        try {
            DB::beginTransaction();
            $terminal->tipo_uso_id = $uso->id;
            $terminal->marca = $request->marca;
            $terminal->modelo = $request->modelo;
            $terminal->imagen = $request->imagen;
            $terminal->save();
            DB::commit();
        } catch (\Exception $e){
            DB::rollback();
            return response()->json([
                'result' => 'ERROR',
                'message' => $e->getMessage()
              ]);
        }

        return redirect()->route('terminales.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $terminal = TipoTerminal::find($id);
        $terminal->delete();
        return redirect()->route('terminales.index');
    }
}
