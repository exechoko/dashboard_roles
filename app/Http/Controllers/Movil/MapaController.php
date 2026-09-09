<?php

namespace App\Http\Controllers\Movil;

use App\Http\Controllers\Controller;
use App\Models\Comisaria;
use App\Models\Sitio;
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
        return view('movil.mapa.index', [
            'puedeVerDependencias' => auth()->user()->can('ver-dependencia'),
        ]);
    }

    public function camarasJson(): JsonResponse
    {
        $camaras = $this->camarasMapaService->paraMapa();

        return response()->json($this->camarasMapaService->geoJson($camaras));
    }

    public function dependenciasJson(): JsonResponse
    {
        abort_unless(auth()->user()->can('ver-dependencia'), 403);

        $features = array_map(function (array $comisaria) {
            return [
                'type' => 'Feature',
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [(float) $comisaria['longitud'], (float) $comisaria['latitud']],
                ],
                'properties' => [
                    'numero' => $comisaria['numero'],
                    'titulo' => $comisaria['titulo'],
                ],
            ];
        }, Comisaria::coordenadasFijas());

        return response()->json(['type' => 'FeatureCollection', 'features' => $features]);
    }

    public function sitiosJson(): JsonResponse
    {
        $sitios = Sitio::where('activo', 0)
            ->whereNotNull('latitud')
            ->whereNotNull('longitud')
            ->get(['id', 'nombre', 'latitud', 'longitud', 'cartel', 'observaciones']);

        $features = $sitios->map(function (Sitio $sitio) {
            return [
                'type' => 'Feature',
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [(float) $sitio->longitud, (float) $sitio->latitud],
                ],
                'properties' => [
                    'id' => $sitio->id,
                    'titulo' => $sitio->nombre,
                    'cartel' => (bool) $sitio->cartel,
                    'observaciones' => $sitio->observaciones,
                ],
            ];
        })->values()->all();

        return response()->json(['type' => 'FeatureCollection', 'features' => $features]);
    }
}
