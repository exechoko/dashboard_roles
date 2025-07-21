<?php

namespace App\Http\Controllers;

use App\Models\TipoCamara;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TipoCamaraController extends Controller
{
    function __construct(){
        $this->middleware('permission:ver-tipo-camara|crear-tipo-camara|editar-tipo-camara|borrar-tipo-camara')->only('index');
        $this->middleware('permission:crear-tipo-camara', ['only'=>['create', 'store']]);
        $this->middleware('permission:editar-tipo-camara', ['only'=>['edit', 'update']]);
        $this->middleware('permission:borrar-tipo-camara', ['only'=>['destroy']]);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $tipoCamaras = TipoCamara::paginate(10);
        return view('tipo_camaras.index', compact('tipoCamaras'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $tipos = [
            'Fija',
            'Fija - FR',
            'Fija - LPR',
            'Fija - LPR NV',
            'Fija - LPR AV',
            'Domo',
            'Domo Dual',
            'Múltiples canales',
            'BDE (Totem)'
        ];
        return view('tipo_camaras.crear', compact('tipos'));
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
            'tipo' => 'required',
            'marca' => 'required',
            'modelo' => 'required',
            'canales' =>'required'
        ], [
            'required' => 'El campo :attribute es necesario completar.'
        ]);

        try {
            DB::beginTransaction();

            if($request->hasFile('imagen')){
                //dd('tiene el campo imagen');
                $file = $request->file('imagen');
                $destinationPath = 'images/uploads/';
                $filename = time() . '-' . $file->getClientOriginalName();
                $uploadSuccess = $request->file('imagen')->move($destinationPath, $filename);

            }

            $tipoCamara = new TipoCamara();
            $tipoCamara->tipo = $request->tipo;
            $tipoCamara->marca = $request->marca;
            $tipoCamara->modelo = $request->modelo;
            $tipoCamara->canales = $request->canales;
            $tipoCamara->observaciones = $request->observaciones;
            $tipoCamara->imagen = $request->hasFile('imagen') ? $destinationPath . $filename : null;

            $tipoCamara->save();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'result' => 'ERROR',
                'message' => $e->getMessage()
              ]);
        }

        return redirect()->route('tipo-camara.index');
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
        $tipoCamara = TipoCamara::find($id);
        $tipos = [
            'Fija',
            'Fija - FR',
            'Fija - LPR',
            'Fija - LPR NV',
            'Fija - LPR AV',
            'Domo',
            'Domo Dual',
            'Múltiples canales',
            'BDE (Totem)'
        ];
        return view('tipo_camaras.editar', compact('tipoCamara', 'tipos'));
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
            'tipo' => 'required',
            'marca' => 'required',
            'modelo' => 'required'
        ], [
            'required' => 'El campo :attribute es necesario completar.'
        ]);


        $tipoCamara = TipoCamara::find($id);

        try {
            DB::beginTransaction();

            if($request->hasFile('imagen')){
                //dd('tiene el campo imagen');
                $file = $request->file('imagen');
                $destinationPath = 'images/uploads/';
                $filename = time() . '-' . $file->getClientOriginalName();
                $uploadSuccess = $request->file('imagen')->move($destinationPath, $filename);

            }

            $tipoCamara->tipo = $request->tipo;
            $tipoCamara->marca = $request->marca;
            $tipoCamara->modelo = $request->modelo;
            $tipoCamara->canales = $request->canales;
            $tipoCamara->imagen = $request->hasFile('imagen') ? $destinationPath . $filename : $tipoCamara->imagen;
            $tipoCamara->save();
            DB::commit();
        } catch (\Exception $e){
            DB::rollback();
            return response()->json([
                'result' => 'ERROR',
                'message' => $e->getMessage()
              ]);
        }

        return redirect()->route('tipo-camara.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $tipoCamara = TipoCamara::find($id);
        $tipoCamara->delete();
        return redirect()->route('tipo-camara.index');
    }
}
