<?php

namespace App\Http\Controllers\Movil;

use App\Http\Controllers\Controller;
use App\Services\CamarasMapaService;
use Illuminate\Http\JsonResponse;

class MapaController extends Controller
{
    public function __construct(private CamarasMapaService $camarasMapaService)
    {
        $this->middleware('permission:ver-camara');
    }

    public function index()
    {
        return view('movil.mapa.index');
    }

    public function camarasJson(): JsonResponse
    {
        $camaras = $this->camarasMapaService->paraMapa();

        return response()->json($this->camarasMapaService->geoJson($camaras));
    }
}
