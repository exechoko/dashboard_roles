<?php

namespace App\Http\Controllers;

use App\Imports\CamaraFisicaImport;
use App\Models\CamaraFisica;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class CamaraFisicaController extends Controller
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
        $camaras = CamaraFisica::where('numero_serie', 'LIKE', '%' . $texto . '%')
            ->orderBy('id', 'asc')
            ->paginate(100);
        //$camaras = Equipo::paginate(5);
        return view('camaras_fisicas.index', compact(
            'camaras', 'texto'
        ));
    }

    public function importExcel(Request $request)
    {
        $file = $request->file('excel_file');
        try {
            Excel::import(new CamaraFisicaImport, $file);
            return redirect()->back()->with('success', 'Los datos se han importado correctamente');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error al importar los datos . ' . $e->getMessage());
        }

    }

}
