<?php

namespace App\Http\Controllers;

use App\Models\Personal;
use Illuminate\Http\Request;

class PersonalController extends Controller
{

    public function __construct()
    {
        $this->middleware('permission:ver-personal')->only(['index', 'show']);
        $this->middleware('permission:crear-personal')->only(['store']);
        $this->middleware('permission:editar-personal')->only(['update']);
        $this->middleware('permission:borrar-personal')->only(['destroy']);
    }

    public function index()
    {
        return Personal::all();
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required',
            'apellido' => 'required',
            'lp' => 'required|digits:5|unique:personals,lp',
            'jerarquia' => 'required'
        ]);

        Personal::create($request->all());

        return response()->json(['ok' => true]);
    }

    public function update(Request $request, $id)
    {
    $personal = Personal::findOrFail($id);

    $personal->update($request->all());

    return response()->json(['ok' => true]);
    }

    public function destroy($id)
    {
        Personal::findOrFail($id)->delete();
        return response()->json(['ok' => true]);
    }
}
