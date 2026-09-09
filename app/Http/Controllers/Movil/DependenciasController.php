<?php

namespace App\Http\Controllers\Movil;

use App\Http\Controllers\Controller;
use App\Models\Destino;
use Illuminate\Http\Request;

class DependenciasController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:ver-dependencia');
    }

    public function index(Request $request)
    {
        $texto = trim((string) $request->get('texto'));

        $dependencias = Destino::when($texto !== '', fn($query) => $query->buscar($texto))
            ->orderByRaw("
                CASE tipo
                    WHEN 'jefatura' THEN 1
                    WHEN 'subjefatura' THEN 2
                    WHEN 'direccion' THEN 3
                    WHEN 'departamental' THEN 4
                    WHEN 'division' THEN 5
                    WHEN 'comisaria' THEN 6
                    WHEN 'seccion' THEN 7
                    WHEN 'destacamento' THEN 8
                    ELSE 9
                END
            ")
            ->orderBy('nombre')
            ->paginate(30)
            ->withQueryString();

        return view('movil.dependencias.index', compact('dependencias', 'texto'));
    }

    public function show(Destino $dependencia)
    {
        $dependencia->load(['padre', 'hijos' => fn($query) => $query->orderBy('nombre')]);

        return view('movil.dependencias.show', compact('dependencia'));
    }
}
