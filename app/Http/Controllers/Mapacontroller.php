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
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class Mapacontroller extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
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
            'camaras.*',
            'sitio.*',
            'tipo_camara.tipo as tipo_camara',
            'tipo_camara.imagen as imagen',
            'tipo_camara.marca as marca',
            'tipo_camara.modelo as modelo',
            'destino.nombre as dependencia',
            DB::raw('sitio.nombre as sitio'),
            DB::raw('sitio.cartel as cartel'),
            DB::raw('sitio.latitud as latitud'),
            DB::raw('sitio.longitud as longitud'),
            DB::raw('camaras.id as numero'),
            DB::raw('camaras.nombre as titulo')
        )
            ->leftJoin('sitio', 'camaras.sitio_id', '=', 'sitio.id')
            ->leftJoin('tipo_camara', 'camaras.tipo_camara_id', '=', 'tipo_camara.id')
            ->leftJoin('destino', 'sitio.destino_id', '=', 'destino.id')
            ->get()->toArray();
        //dd($camaras);

        $antenas = [
            //PARANA
            [
                'latitud' => -31.72652,
                'longitud' => -60.53293,
                'titulo' => 'SBS 1',
                'numero' => 1
            ],
            [
                'latitud' => -31.75109,
                'longitud' => -60.48563,
                'titulo' => 'SBS 2',
                'numero' => 2
            ],
            [
                'latitud' => -31.77106,
                'longitud' => -60.52482,
                'titulo' => 'SBS 3',
                'numero' => 3
            ],
            //CONCORDIA
            [
                'latitud' => -31.324043,
                'longitud' => -58.012072,
                'titulo' => 'SBS 11',
                'numero' => 11
            ],
            [
                'latitud' => -31.391542,
                'longitud' => -58.032703,
                'titulo' => 'SBS 12',
                'numero' => 12
            ]
        ];

        $jurisdicciones = Comisaria::select(
            'comisarias.jurisdiccion'
        )->get();

        $fijas = Camara::whereHas('tipoCamara', function ($query) {
            $query->where('tipo', 'Fija');
        })->count();
        $fijasFR = Camara::whereHas('tipoCamara', function ($query) {
            $query->where('tipo', 'Fija - FR');
        })->count();
        $fijasLPR = Camara::whereHas('tipoCamara', function ($query) {
            $query->where('tipo', 'Fija - LPR')->orWhere('tipo', 'Fija - LPR NV')->orWhere('tipo', 'Fija - LPR AV');
        })->count();
        $domos = Camara::whereHas('tipoCamara', function ($query) {
            $query->where('tipo', 'Domo');
        })->count(); //Camara::where('tipo', 'Domo')->count();
        $domosDuales = Camara::whereHas('tipoCamara', function ($query) {
            $query->where('tipo', 'Domo Dual');
        })->count();

        $totalCam = Camara::all()->count();
        $totalCamaras = Camara::select(
            'camaras.id',
            'tipo_camara.canales as canales'
        )
            ->leftJoin('tipo_camara', 'camaras.tipo_camara_id', '=', 'tipo_camara.id')
            ->get();
        $cantidadCanales = 0;
        foreach ($totalCamaras as $camara) {
            $cantidadCanales += $camara->canales;
        }

        // Contar las cámaras en Paraná
        $camarasParana = Camara::join('sitio', 'camaras.sitio_id', '=', 'sitio.id')
            ->where('sitio.localidad', 'Paraná')
            ->count();
        // Contar las cámaras en San Benito
        $camarasSanBenito = Camara::join('sitio', 'camaras.sitio_id', '=', 'sitio.id')
            ->where('sitio.localidad', 'San Benito')
            ->count();
        // Contar las cámaras en Colonia Avellaneda
        $camarasCniaAvellaneda = Camara::join('sitio', 'camaras.sitio_id', '=', 'sitio.id')
            ->where('sitio.localidad', 'Colonia Avellaneda')
            ->count();
        // Contar las cámaras en Oro Verde
        $camarasOroVerde = Camara::join('sitio', 'camaras.sitio_id', '=', 'sitio.id')
            ->where('sitio.localidad', 'Oro Verde')
            ->count();

        $sitios = Sitio::count();
        $sitiosParana = Sitio::where('localidad', 'Paraná')->count();
        $sitiosCniaAvellaneda = Sitio::where('localidad', 'Colonia Avellaneda')->count();
        $sitiosSanBenito = Sitio::where('localidad', 'San Benito')->count();
        $sitiosOroVerde = Sitio::where('localidad', 'Oro Verde')->count();

        return response()->view(
            'mapa.mapa',
            [
                'comisarias' => $comisarias,
                'antenas' => $antenas,
                'camaras' => $camaras,
                'canales' => $cantidadCanales,
                'jurisdicciones' => $jurisdicciones,
                'fijas' => $fijas,
                'fijasFR' => $fijasFR,
                'fijasLPR' => $fijasLPR,
                'domos' => $domos,
                'domosDuales' => $domosDuales,
                'total' => $totalCam,
                'sitios' => $sitios,
                'sitiosParana' => $sitiosParana,
                'sitiosCniaAvellaneda' => $sitiosCniaAvellaneda,
                'sitiosSanBenito' => $sitiosSanBenito,
                'sitiosOroVerde' => $sitiosOroVerde,
                'camarasParana' => $camarasParana, // Agrega esta línea
                'camarasSanBenito' => $camarasSanBenito, // Agrega esta línea
                'camarasCniaAvellaneda' => $camarasCniaAvellaneda, // Agrega esta línea
                'camarasOroVerde' => $camarasOroVerde // Agrega esta línea
            ]
        );
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
        //dd($camaras);
        /*$camaras = [
            [
                'latitud' => -31.72988,
                'longitud' => -60.53557,
                'titulo' => 'Camara 1°',
                'numero' => 1
            ],
            [
                'latitud' => -31.73755,
                'longitud' => -60.5294,
                'titulo' => 'Camara 2°',
                'numero' => 2
            ],
            [
                'latitud' => -31.757398,
                'longitud' => -60.595877,
                'titulo' => 'Camara 3°',
                'numero' => 3
            ]
        ];*/

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


        return view('mapa.mapa', ['comisarias' => $comisarias, 'antenas' => $antenas, 'camaras' => $camaras]);
    }

    public function exportarExcel()
    {
        $cc = new CamaraController;
        return $cc->exportExcel();
        //return Excel::download(new CamarasExport, 'ListadoCamaras_' . Carbon::now() . '.xlsx');
    }
}
