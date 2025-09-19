<?php

namespace App\Http\Controllers;

use App\Models\Bodycam;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BodycamController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:ver-bodycam,ver-menu-bodycams')->only('index');
        $this->middleware('can:crear-bodycam')->only(['create', 'store']);
        $this->middleware('can:editar-bodycam')->only(['edit', 'update']);
        $this->middleware('can:borrar-bodycam')->only('destroy');
    }

    public function index(Request $request)
    {
        $texto = $request->get('texto');
        $estado = $request->get('estado');

        $bodycams = Bodycam::query();

        if ($texto) {
            $bodycams->where(function ($query) use ($texto) {
                $query->where('codigo', 'LIKE', '%' . $texto . '%')
                    ->orWhere('numero_serie', 'LIKE', '%' . $texto . '%')
                    ->orWhere('imei', 'LIKE', '%' . $texto . '%')
                    ->orWhere('marca', 'LIKE', '%' . $texto . '%')
                    ->orWhere('modelo', 'LIKE', '%' . $texto . '%');
            });
        }

        if ($estado) {
            $bodycams->where('estado', $estado);
        }

        $bodycams = $bodycams->orderBy('id', 'DESC')->paginate(10);

        $estados = [
            Bodycam::ESTADO_DISPONIBLE => 'Disponible',
            Bodycam::ESTADO_ENTREGADA => 'Entregada',
            Bodycam::ESTADO_PERDIDA => 'Perdida',
            Bodycam::ESTADO_MANTENIMIENTO => 'En Mantenimiento',
            Bodycam::ESTADO_DADA_BAJA => 'Dada de Baja'
        ];

        return view('bodycams.index', compact('bodycams', 'texto', 'estado', 'estados'));
    }

    public function create()
    {
        return view('bodycams.crear');
    }

    public function store(Request $request)
    {
        $request->validate([
            'codigo' => 'required|unique:bodycams',
            'numero_serie' => 'required|unique:bodycams',
            'marca' => 'required',
            'modelo' => 'required',
            'estado' => 'required|in:disponible,entregada,perdida,mantenimiento,dada_baja'
        ]);

        $bodycam = new Bodycam($request->all());
        $bodycam->usuario_creador = Auth::id();
        $bodycam->save();

        return redirect()->route('bodycams.index')
            ->with('success', 'Bodycam creada exitosamente');
    }

    public function show($id)
    {
        $bodycam = Bodycam::find($id);
        return view('bodycams.show', compact('bodycam'));
    }

    public function edit($id)
    {
        $bodycam = Bodycam::find($id);
        return view('bodycams.editar', compact('bodycam'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'codigo' => 'required|unique:bodycams,codigo,' . $id,
            'numero_serie' => 'required|unique:bodycams,numero_serie,' . $id,
            'marca' => 'required',
            'modelo' => 'required',
            'estado' => 'required|in:disponible,entregada,perdida,mantenimiento,dada_baja'
        ]);

        $bodycam = Bodycam::find($id);
        $bodycam->update($request->all());

        return redirect()->route('bodycams.index')
            ->with('success', 'Bodycam actualizada exitosamente');
    }

    public function destroy($id)
    {
        $bodycam = Bodycam::find($id);
        $bodycam->delete();

        return redirect()->route('bodycams.index')
            ->with('success', 'Bodycam eliminada exitosamente');
    }
}
