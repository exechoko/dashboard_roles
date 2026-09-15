<?php

namespace App\Http\Controllers;

use App\Http\Requests\AntenaRequest;
use App\Models\Antena;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AntenaController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:ver-antena|crear-antena|editar-antena|borrar-antena')->only('index');
        $this->middleware('permission:crear-antena', ['only' => ['create', 'store']]);
        $this->middleware('permission:editar-antena', ['only' => ['edit', 'update']]);
        $this->middleware('permission:borrar-antena', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $texto = trim($request->get('texto'));
        $antenas = Antena::where('nombre', 'LIKE', '%' . $texto . '%')
            ->orWhere('localidad', 'LIKE', '%' . $texto . '%')
            ->orderBy('id', 'asc')
            ->paginate(20);

        return view('antenas.index', compact('antenas', 'texto'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('antenas.crear');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AntenaRequest $request)
    {
        try {
            DB::beginTransaction();
            $antena = new Antena;
            $antena->nombre = $request->nombre;
            $antena->localidad = $request->localidad;
            $antena->ubicacion = $request->ubicacion;
            $antena->latitud = $request->latitud;
            $antena->longitud = $request->longitud;
            $antena->altura = $request->altura;
            $antena->activa = $request->boolean('activa');
            $antena->observaciones = $request->observaciones;
            $antena->save();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'result' => 'ERROR',
                'message' => $e->getMessage()
            ]);
        }

        return redirect()->route('antenas.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(Antena $antena)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $antena = Antena::find($id);

        return view('antenas.editar', compact('antena'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(AntenaRequest $request, Antena $antena)
    {
        try {
            DB::beginTransaction();
            $antena->nombre = $request->nombre;
            $antena->localidad = $request->localidad;
            $antena->ubicacion = $request->ubicacion;
            $antena->latitud = $request->latitud;
            $antena->longitud = $request->longitud;
            $antena->altura = $request->altura;
            $antena->activa = $request->boolean('activa');
            $antena->observaciones = $request->observaciones;
            $antena->save();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'result' => 'ERROR',
                'message' => $e->getMessage()
            ]);
        }

        return redirect()->route('antenas.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Antena $antena)
    {
        $antena->delete();

        return redirect()->route('antenas.index');
    }
}
