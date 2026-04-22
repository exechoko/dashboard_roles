<?php

namespace App\Http\Controllers;

use App\Models\Personal;
use Illuminate\Http\Request;

class PersonalController extends Controller
{
    public function index()
    {
        if (request()->expectsJson()) {
            return \App\Models\Personal::all();
        }

        return view('tareas.personal-efectivo.index');
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
