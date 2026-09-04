<?php

namespace App\Http\Controllers\Movil;

use App\Http\Controllers\Controller;
use App\Models\Camara;
use Illuminate\Http\Request;

class CamarasController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:ver-camara');
    }

    public function index(Request $request)
    {
        $texto = trim((string) $request->get('texto'));

        $camaras = Camara::with(['tipoCamara', 'sitio.destino'])
            ->when($texto !== '', function ($query) use ($texto) {
                $query->where(function ($q) use ($texto) {
                    $q->where('nombre', 'like', "%{$texto}%")
                        ->orWhereHas('sitio', function ($sitio) use ($texto) {
                            $sitio->where('nombre', 'like', "%{$texto}%");
                        });
                });
            })
            ->orderBy('nombre')
            ->paginate(20)
            ->withQueryString();

        return view('movil.camaras.index', compact('camaras', 'texto'));
    }

    public function show(Camara $camara)
    {
        $camara->load(['tipoCamara', 'sitio.destino', 'camaraFisica']);

        // Vuelve a la búsqueda tal cual quedó (texto, página), no al listado
        // vacío: solo se confía en la URL anterior si de verdad viene del
        // listado de cámaras.
        $volver = str_starts_with(url()->previous(), route('movil.camaras.index'))
            ? url()->previous()
            : route('movil.camaras.index');

        return view('movil.camaras.show', compact('camara', 'volver'));
    }
}
