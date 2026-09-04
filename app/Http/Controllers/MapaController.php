<?php

namespace App\Http\Controllers;

use App\Exports\CamarasExport;
use App\Models\Camara;
use App\Models\Comisaria;
use App\Models\Departamental;
use App\Models\Destacamento;
use App\Models\Destino;
use App\Models\Direccion;
use App\Models\Division;
use App\Models\Seccion;
use App\Models\Sitio;
use App\Models\TipoCamara;
use App\Services\CamarasMapaService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

use function PHPUnit\Framework\callback;

class MapaController extends Controller
{
    public function __construct(private CamarasMapaService $camarasMapaService)
    {
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $comisarias = $this->comisariasFijas();
        $camaras = $this->camarasMapaService->paraMapa();
        $sitios = $this->sitiosParaMapa();
        $antenas = $this->antenasFijas();
        $jurisdicciones = $this->jurisdiccionesDeComisarias();

        return response()->view(
            'mapa.mapa',
            array_merge(
                [
                    'comisarias' => $comisarias,
                    'antenas' => $antenas,
                    'camaras' => $camaras,
                    'jurisdicciones' => $jurisdicciones,
                    'sitios' => $sitios,
                ],
                $this->estadisticasCamaras()
            )
        );
    }

    /**
     * Muestra la vista 3D del mapa (cámaras, comisarías, antenas y sitios
     * como volúmenes extruidos sobre edificios reales en MapLibre GL).
     */
    public function vista3d()
    {
        $camaras = $this->camarasMapaService->paraMapa();
        $sitios = $this->sitiosParaMapa();
        $comisarias = $this->comisariasFijas();
        $antenas = $this->antenasFijas();
        $jurisdicciones = $this->jurisdiccionesDeComisarias();

        $geojson = [
            'camaras' => $this->camarasMapaService->geoJson($camaras),
            'sitios' => $this->sitiosGeoJson($sitios),
            'comisarias' => $this->comisariasGeoJson($comisarias),
            'antenas' => $this->antenasGeoJson($antenas),
            'jurisdicciones' => $this->jurisdiccionesGeoJson($jurisdicciones),
        ];

        return response()->view(
            'mapa.mapa3d',
            array_merge(
                [
                    'camaras' => $camaras,
                    'geojson' => $geojson,
                    'stadiaApiKey' => config('services.stadia.api_key'),
                ],
                $this->estadisticasCamaras()
            )
        );
    }

    /**
     * Comisarías con coordenadas fijas (no tienen tabla propia de ubicación).
     *
     * @return array<int, array{latitud: float, longitud: float, titulo: string, numero: int}>
     */
    private function comisariasFijas(): array
    {
        return [
            ['latitud' => -31.72978, 'longitud' => -60.53547, 'titulo' => 'Cria. 1°', 'numero' => 1],
            ['latitud' => -31.73735, 'longitud' => -60.5284, 'titulo' => 'Cria. 2°', 'numero' => 2],
            ['latitud' => -31.757298, 'longitud' => -60.495857, 'titulo' => 'Cria. 3°', 'numero' => 3],
            ['latitud' => -31.73771, 'longitud' => -60.51383, 'titulo' => 'Cria. 4°', 'numero' => 4],
            ['latitud' => -31.73001, 'longitud' => -60.54851, 'titulo' => 'Cria. 5°', 'numero' => 5],
            ['latitud' => -31.74674, 'longitud' => -60.5364, 'titulo' => 'Cria. 6°', 'numero' => 6],
            ['latitud' => -31.73711, 'longitud' => -60.45818, 'titulo' => 'Cria. 7°', 'numero' => 7],
            ['latitud' => -31.72208, 'longitud' => -60.51665, 'titulo' => 'Cria. 8°', 'numero' => 8],
            ['latitud' => -31.74051, 'longitud' => -60.55312, 'titulo' => 'Cria. 9°', 'numero' => 9],
            ['latitud' => -31.75655, 'longitud' => -60.51133, 'titulo' => 'Cria. 10°', 'numero' => 10],
            ['latitud' => -31.70670, 'longitud' => -60.56671, 'titulo' => 'Cria. 11°', 'numero' => 11],
            ['latitud' => -31.75109, 'longitud' => -60.48563, 'titulo' => 'Cria. 12°', 'numero' => 12],
            ['latitud' => -31.77106, 'longitud' => -60.52482, 'titulo' => 'Cria. 13°', 'numero' => 13],
            ['latitud' => -31.73017, 'longitud' => -60.49726, 'titulo' => 'Cria. 14°', 'numero' => 14],
            ['latitud' => -31.77032, 'longitud' => -60.48219, 'titulo' => 'Cria. 15°', 'numero' => 15],
            ['latitud' => -31.73434, 'longitud' => -60.55248, 'titulo' => 'Cria. 16°', 'numero' => 16],
            ['latitud' => -31.72189, 'longitud' => -60.54260, 'titulo' => 'Cria. 17°', 'numero' => 17],
        ];
    }

    /**
     * Antenas con coordenadas fijas (Paraná + Concordia).
     *
     * @return array<int, array{latitud: float, longitud: float, titulo: string, numero: int}>
     */
    private function antenasFijas(): array
    {
        return [
            // PARANA
            ['latitud' => -31.72652, 'longitud' => -60.53293, 'titulo' => 'SBS 1', 'numero' => 1],
            ['latitud' => -31.75109, 'longitud' => -60.48563, 'titulo' => 'SBS 2', 'numero' => 2],
            ['latitud' => -31.77106, 'longitud' => -60.52482, 'titulo' => 'SBS 3', 'numero' => 3],
            // CONCORDIA
            ['latitud' => -31.324043, 'longitud' => -58.012072, 'titulo' => 'SBS 11', 'numero' => 11],
            ['latitud' => -31.391542, 'longitud' => -58.032703, 'titulo' => 'SBS 12', 'numero' => 12],
        ];
    }

    /**
     * Todos los sitios (activos e inactivos) para el mapa.
     *
     * @return array<int, array<string, mixed>>
     */
    private function sitiosParaMapa(): array
    {
        return Sitio::select(
            '*',
            DB::raw('sitio.id as numero'),
            DB::raw('sitio.nombre as titulo')
        )->get()->toArray();
    }

    /**
     * Jurisdicciones (polígonos) de cada comisaría.
     */
    private function jurisdiccionesDeComisarias()
    {
        return Comisaria::select('comisarias.jurisdiccion')->get();
    }

    /**
     * Estadísticas de cámaras y sitios usadas en el header del mapa (2D y 3D).
     *
     * @return array<string, mixed>
     */
    private function estadisticasCamaras(): array
    {
        $activoSitio = function ($query) {
            $query->where('activo', 1);
        };

        $fijas = Camara::whereHas('tipoCamara', fn($q) => $q->where('tipo', 'Fija'))
            ->whereHas('sitio', $activoSitio)
            ->count();

        $fijasFR = Camara::whereHas('tipoCamara', fn($q) => $q->where('tipo', 'Fija - FR'))
            ->whereHas('sitio', $activoSitio)
            ->count();

        $fijasLPR = Camara::whereHas('tipoCamara', fn($q) =>
            $q->whereIn('tipo', ['Fija - LPR', 'Fija - LPR NV', 'Fija - LPR AV']))
            ->whereHas('sitio', $activoSitio)
            ->count();

        $domos = Camara::whereHas('tipoCamara', fn($q) => $q->where('tipo', 'Domo'))
            ->whereHas('sitio', $activoSitio)
            ->count();

        $domosDuales = Camara::whereHas('tipoCamara', fn($q) => $q->where('tipo', 'Domo Dual'))
            ->whereHas('sitio', $activoSitio)
            ->count();

        $bde = Camara::whereHas('tipoCamara', fn($q) => $q->where('tipo', 'BDE (Totem)'))
            ->whereHas('sitio', $activoSitio)
            ->count();

        $total = Camara::select('camaras.id', 'tipo_camara.tipo', 'sitio.activo')
            ->where('sitio.activo', 1)
            ->where('tipo_camara.tipo', '!=', 'BDE (Totem)')
            ->leftJoin('tipo_camara', 'camaras.tipo_camara_id', '=', 'tipo_camara.id')
            ->leftJoin('sitio', 'camaras.sitio_id', '=', 'sitio.id')
            ->get()->count();

        $totalCamaras = Camara::select(
            'camaras.id',
            'tipo_camara.tipo',
            'tipo_camara.canales as canales',
            'sitio.activo'
        )
            ->where('sitio.activo', 1)
            ->where('tipo_camara.tipo', '!=', 'BDE (Totem)')
            ->leftJoin('sitio', 'camaras.sitio_id', '=', 'sitio.id')
            ->leftJoin('tipo_camara', 'camaras.tipo_camara_id', '=', 'tipo_camara.id')
            ->get();
        $cantidadCanales = 0;
        foreach ($totalCamaras as $camara) {
            $cantidadCanales += $camara->canales;
        }

        $camarasPorLocalidad = function (string $localidad) {
            return Camara::select('camaras.id', 'tipo_camara.tipo', 'sitio.activo')
                ->leftjoin('tipo_camara', 'camaras.tipo_camara_id', '=', 'tipo_camara.id')
                ->leftjoin('sitio', 'camaras.sitio_id', '=', 'sitio.id')
                ->where('tipo_camara.tipo', '!=', 'BDE (Totem)')
                ->where('sitio.activo', 1)
                ->where('sitio.localidad', $localidad)
                ->count();
        };

        $sitiosActivos = Sitio::where('activo', 1)
            ->select('localidad', DB::raw('count(*) as total'))
            ->groupBy('localidad')
            ->get()
            ->keyBy('localidad');

        return [
            'fijas' => $fijas,
            'fijasFR' => $fijasFR,
            'fijasLPR' => $fijasLPR,
            'domos' => $domos,
            'domosDuales' => $domosDuales,
            'bde' => $bde,
            'total' => $total,
            'canales' => $cantidadCanales,
            'camarasParana' => $camarasPorLocalidad('Paraná'),
            'camarasSanBenito' => $camarasPorLocalidad('San Benito'),
            'camarasCniaAvellaneda' => $camarasPorLocalidad('Colonia Avellaneda'),
            'camarasOroVerde' => $camarasPorLocalidad('Oro Verde'),
            'cantidadSitios' => $sitiosActivos->sum('total'),
            'sitiosParana' => $sitiosActivos['Paraná']->total ?? 0,
            'sitiosCniaAvellaneda' => $sitiosActivos['Colonia Avellaneda']->total ?? 0,
            'sitiosSanBenito' => $sitiosActivos['San Benito']->total ?? 0,
            'sitiosOroVerde' => $sitiosActivos['Oro Verde']->total ?? 0,
        ];
    }

    /**
     * GeoJSON de sitios inactivos (Point) para la vista 3D.
     *
     * @param array<int, array<string, mixed>> $sitios
     * @return array<string, mixed>
     */
    private function sitiosGeoJson(array $sitios): array
    {
        $features = [];

        foreach ($sitios as $sitio) {
            if (($sitio['activo'] ?? 1) != 0) {
                continue;
            }
            $lat = $sitio['latitud'] ?? null;
            $lng = $sitio['longitud'] ?? null;
            if (!is_numeric($lat) || !is_numeric($lng)) {
                continue;
            }

            $features[] = [
                'type' => 'Feature',
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [(float) $lng, (float) $lat],
                ],
                'properties' => [
                    'id' => $sitio['numero'],
                    'titulo' => $sitio['titulo'],
                    'cartel' => isset($sitio['cartel']) ? (bool) $sitio['cartel'] : null,
                    'observaciones' => $sitio['observaciones'] ?? null,
                ],
            ];
        }

        return ['type' => 'FeatureCollection', 'features' => $features];
    }

    /**
     * GeoJSON de comisarías (Point) para la vista 3D.
     *
     * @param array<int, array{latitud: float, longitud: float, titulo: string, numero: int}> $comisarias
     * @return array<string, mixed>
     */
    private function comisariasGeoJson(array $comisarias): array
    {
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
        }, $comisarias);

        return ['type' => 'FeatureCollection', 'features' => $features];
    }

    /**
     * GeoJSON de antenas (Point) para la vista 3D.
     *
     * @param array<int, array{latitud: float, longitud: float, titulo: string, numero: int}> $antenas
     * @return array<string, mixed>
     */
    private function antenasGeoJson(array $antenas): array
    {
        $features = array_map(function (array $antena) {
            return [
                'type' => 'Feature',
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [(float) $antena['longitud'], (float) $antena['latitud']],
                ],
                'properties' => [
                    'numero' => $antena['numero'],
                    'titulo' => $antena['titulo'],
                ],
            ];
        }, $antenas);

        return ['type' => 'FeatureCollection', 'features' => $features];
    }

    /**
     * GeoJSON de jurisdicciones (Polygon) para la vista 3D.
     *
     * @param \Illuminate\Support\Collection $jurisdicciones
     * @return array<string, mixed>
     */
    private function jurisdiccionesGeoJson($jurisdicciones): array
    {
        $features = [];

        foreach ($jurisdicciones as $jurisdiccion) {
            if (empty($jurisdiccion->jurisdiccion)) {
                continue;
            }

            $puntos = json_decode($jurisdiccion->jurisdiccion, true);
            if (!is_array($puntos) || count($puntos) < 3) {
                continue;
            }

            $anillo = array_map(fn($p) => [(float) $p['lng'], (float) $p['lat']], $puntos);
            $anillo[] = $anillo[0];

            $features[] = [
                'type' => 'Feature',
                'geometry' => [
                    'type' => 'Polygon',
                    'coordinates' => [$anillo],
                ],
                'properties' => (object) [],
            ];
        }

        return ['type' => 'FeatureCollection', 'features' => $features];
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
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
        //
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
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function showMap()
    {
        $comisarias = [
            [
                'latitud' => -31.72978,
                'longitud' => -60.53547,
                'titulo' => 'Cria. 1°',
                'numero' => 1
            ],
            [
                'latitud' => -31.73735,
                'longitud' => -60.5284,
                'titulo' => 'Cria. 2°',
                'numero' => 2
            ],
            [
                'latitud' => -31.757298,
                'longitud' => -60.495857,
                'titulo' => 'Cria. 3°',
                'numero' => 3
            ],
            [
                'latitud' => -31.73771,
                'longitud' => -60.51383,
                'titulo' => 'Cria. 4°',
                'numero' => 4
            ],
            [
                'latitud' => -31.73001,
                'longitud' => -60.54851,
                'titulo' => 'Cria. 5°',
                'numero' => 5
            ],
            [
                'latitud' => -31.74674,
                'longitud' => -60.5364,
                'titulo' => 'Cria. 6°',
                'numero' => 6
            ],
            [
                'latitud' => -31.73711,
                'longitud' => -60.45818,
                'titulo' => 'Cria. 7°',
                'numero' => 7
            ],
            [
                'latitud' => -31.72208,
                'longitud' => -60.51665,
                'titulo' => 'Cria. 8°',
                'numero' => 8
            ],
            [
                'latitud' => -31.74051,
                'longitud' => -60.55312,
                'titulo' => 'Cria. 9°',
                'numero' => 9
            ],
            [
                'latitud' => -31.75655,
                'longitud' => -60.51133,
                'titulo' => 'Cria. 10°',
                'numero' => 10
            ],
            [
                'latitud' => -31.70670,
                'longitud' => -60.56671,
                'titulo' => 'Cria. 11°',
                'numero' => 11
            ],
            [
                'latitud' => -31.75109,
                'longitud' => -60.48563,
                'titulo' => 'Cria. 12°',
                'numero' => 12
            ],
            [
                'latitud' => -31.77106,
                'longitud' => -60.52482,
                'titulo' => 'Cria. 13°',
                'numero' => 13
            ],
            [
                'latitud' => -31.73017,
                'longitud' => -60.49726,
                'titulo' => 'Cria. 14°',
                'numero' => 14
            ],
            [
                'latitud' => -31.77032,
                'longitud' => -60.48219,
                'titulo' => 'Cria. 15°',
                'numero' => 15
            ],
            [
                'latitud' => -31.73434,
                'longitud' => -60.55248,
                'titulo' => 'Cria. 16°',
                'numero' => 16
            ],
            [
                'latitud' => -31.72189,
                'longitud' => -60.54260,
                'titulo' => 'Cria. 17°',
                'numero' => 17
            ]
        ];

        //$camaras = Camara::all();
        $camaras = Camara::select(
            'camaras.id',
            'camaras.nombre',
            'camaras.tipo',
            'camaras.inteligencia',
            'camaras.latitud',
            'camaras.longitud',
            'camaras.sitio',
            DB::raw('camaras.id as numero'),
            DB::raw('camaras.nombre as titulo')
        )->get()->toArray();

        $antenas = [
            [
                'latitud' => -31.73988,
                'longitud' => -60.53557,
                'titulo' => 'Antena 1°',
                'numero' => 1
            ],
            [
                'latitud' => -31.74755,
                'longitud' => -60.5294,
                'titulo' => 'Antena 2°',
                'numero' => 2
            ],
            [
                'latitud' => -31.747398,
                'longitud' => -60.595877,
                'titulo' => 'Antena 3°',
                'numero' => 3
            ]
        ];

        // Convertir el array en formato JSON
        //$jsonUbicaciones = json_encode($ubicaciones);

        return view(
            'mapa.mapa',
            [
                'comisarias' => $comisarias,
                'antenas' => $antenas,
                'camaras' => $camaras
            ]
        );
    }

    public function exportarExcel()
    {
        $cc = new CamaraController;
        return $cc->exportExcel();
        //return Excel::download(new CamarasExport, 'ListadoCamaras_' . Carbon::now() . '.xlsx');
    }
}
