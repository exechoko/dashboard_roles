<?php

namespace App\Http\Controllers;

use App\Models\GeocodificacionInversa;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CecocoController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:ver-moviles-cecoco')->only('indexMoviles');
        $this->middleware('permission:ver-llamadas-cecoco')->only('indexLlamadas');
        $this->middleware('permission:ver-eventos-cecoco')->only('indexLlamadas');
        /*$this->middleware('permission:crear-camara', ['only' => ['create', 'store']]);
        $this->middleware('permission:editar-camara', ['only' => ['edit', 'update']]);
        $this->middleware('permission:borrar-camara', ['only' => ['destroy']]);*/
    }

    public function indexMoviles()
    {

        // Obtener los recursos únicos
        $recursos = DB::connection('mysql_second')
            ->table('posicionesgps')
            ->distinct()
            ->pluck('recurso')
            ->toArray();

        return view('cecoco.moviles.index', compact('recursos'));
    }

    public function indexLlamadas()
    {
        $protocolos = DB::connection('mysql_second')
            ->table('protocoloscomunicaciones')
            ->get();

        return view('cecoco.llamadas.index', compact('protocolos'));
    }

    public function indexMapaCalor()
    {
        /*$servicios = [
            ['latitud' => -31.72978, 'longitud' => -60.53547, 'tipo' => 'Robo'],
            ['latitud' => -31.72981, 'longitud' => -60.53548, 'tipo' => 'Robo'],
            ['latitud' => -31.72982, 'longitud' => -60.53547, 'tipo' => 'Robo'],
            ['latitud' => -31.73771, 'longitud' => -60.51383, 'tipo' => 'Robo'],
            ['latitud' => -31.73001, 'longitud' => -60.54851, 'tipo' => 'Hurto'],
            ['latitud' => -31.74674, 'longitud' => -60.5364, 'tipo' => 'Hurto'],
            ['latitud' => -31.73711, 'longitud' => -60.45818, 'tipo' => 'Arrebato'],
            ['latitud' => -31.72208, 'longitud' => -60.51665, 'tipo' => 'Arrebato'],
            ['latitud' => -31.74051, 'longitud' => -60.55312, 'tipo' => 'Robo'],
            ['latitud' => -31.75655, 'longitud' => -60.51133, 'tipo' => 'Hurto'],
            ['latitud' => -31.70670, 'longitud' => -60.56671, 'tipo' => 'Accidente'],
            ['latitud' => -31.75109, 'longitud' => -60.48563, 'tipo' => 'Accidente'],
            ['latitud' => -31.77106, 'longitud' => -60.52482, 'tipo' => 'Hurto'],
            ['latitud' => -31.73017, 'longitud' => -60.49726, 'tipo' => 'Hurto'],
            ['latitud' => -31.77032, 'longitud' => -60.48219, 'tipo' => 'Robo'],
            ['latitud' => -31.73434, 'longitud' => -60.55248, 'tipo' => 'Accidente'],
            ['latitud' => -31.72189, 'longitud' => -60.54260, 'tipo' => 'Accidente']
        ];*/

        $tipificaciones = DB::connection('mysql_second')
            ->table('tiposservicio')
            ->distinct()
            ->pluck('nombre')
            ->toArray();

        //dd($servicios);

        return view('cecoco.mapas.mapa_de_calor', compact('tipificaciones'));
    }

    public function indexMapaCecocoEnVivo()
    {
        //dd($recursos);
        return view('cecoco.mapas.mapa_cecoco_en_vivo');
    }

    public function getRecursosCecoco()
    {
        $fecha = Carbon::parse('2023-11-01 09:21:08');
        $fecha_actual = $fecha->copy();
        $fecha_desde = $fecha->subMinutes(100); // Restar 10 minutos a la fecha actual
        $fecha_hasta = $fecha_actual->copy(); // La fecha actual

        try {
            $recursos = DB::connection('mysql_second')
                ->table('ultimasposicionesgps')
                ->select(
                    'ultimasposicionesgps.*',
                    'recursos.*',
                    'tiposrecurso.nombre as tipo_recurso',
                )
                ->join('recursos', 'recursos.id', '=', 'ultimasposicionesgps.recursos_id')
                ->join('tiposrecurso', 'tiposrecurso.id', '=', 'recursos.idTipo')
                ->whereBetween('ultimasposicionesgps.fecha', [$fecha_desde, $fecha_hasta])
                ->where('ultimasposicionesgps.latitud', '!=', '0.0')
                ->where('ultimasposicionesgps.longitud', '!=', '0.0')
                ->get()
                ->map(function ($result) {
                    // Convertir las coordenadas de radianes a grados decimales
                    $result->latitud_decimal = round($result->latitud / 0.0174533, 7);
                    $result->longitud_decimal = round($result->longitud / 0.0174533, 7);
                    return $result;
                });

            $servicios = DB::connection('mysql_second')
                ->table('servicios')
                ->select(
                    'servicios.*',
                    'posicionamientosmapaservicio.*',
                    'sucesosservicio_policia.descripcion',
                    'sucesosservicio_policia.direccion',
                    'relacion_tiposservicio_servicios.*',
                    'tiposservicio.nombre as tipo_servicio',
                )
                ->join('posicionamientosmapaservicio', function ($join) {
                    $join->on('servicios.id', '=', 'posicionamientosmapaservicio.idServicio')
                        ->where('posicionamientosmapaservicio.latitud', '!=', '0.0')
                        ->where('posicionamientosmapaservicio.longitud', '!=', '0.0');
                })
                ->join('sucesosservicio_policia', 'servicios.id', '=', 'sucesosservicio_policia.idServicio')
                ->join('relacion_tiposservicio_servicios', 'servicios.id', '=', 'relacion_tiposservicio_servicios.servicios_id')
                ->join('tiposservicio', 'tiposservicio.id', '=', 'relacion_tiposservicio_servicios.tiposservicio_id')
                ->whereBetween('servicios.fechaCreacion', [$fecha_desde, $fecha_hasta])
                ->get();
            //dd($servicios);

            return response()->json([
                'recursos' => $recursos,
                'servicios' => $servicios
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 200);
        }
    }

    public function getServicios(Request $request)
    {
        /*$fecha = Carbon::parse('2023-11-01 09:21:08');
        $fecha_actual = $fecha->copy(); // Clonar el objeto original
        $fecha_hace_una_semana = $fecha->subWeek();
        $fecha_un_mes_antes = $fecha->copy()->subMonth(); // Obtener la fecha de un mes antes

        $fecha_actual_formateada = $fecha_actual->format('Y-m-d H:i:s');
        $fecha_hace_una_semana_formateada = $fecha_hace_una_semana->format('Y-m-d H:i:s');
        $fecha_un_mes_antes_formateada = $fecha_un_mes_antes->format('Y-m-d H:i:s');*/
        $fecha_desde = \Carbon\Carbon::parse($request->fecha_desde)->format('Y-m-d H:i:s');
        $fecha_hasta = \Carbon\Carbon::parse($request->fecha_hasta)->format('Y-m-d H:i:s');
        $tipificacion = $request->tipificacion;
        try {
            $servicios = DB::connection('mysql_second')
                ->table('servicios_historico')
                ->select(
                    'servicios_historico.*',
                    'posicionamientosmapaservicio_historico.*',
                    'sucesosservicio_policia_historico.descripcion',
                    'sucesosservicio_policia_historico.direccion',
                    'relacion_tiposservicio_servicios_historico.*',
                )
                ->join('posicionamientosmapaservicio_historico', function ($join) {
                    $join->on('servicios_historico.id', '=', 'posicionamientosmapaservicio_historico.idServicio')
                        ->where('posicionamientosmapaservicio_historico.latitud', '!=', '0.0')
                        ->where('posicionamientosmapaservicio_historico.longitud', '!=', '0.0');
                })
                ->join('sucesosservicio_policia_historico', 'servicios_historico.id', '=', 'sucesosservicio_policia_historico.idServicio')
                ->join('relacion_tiposservicio_servicios_historico', 'servicios_historico.id', '=', 'relacion_tiposservicio_servicios_historico.servicios_id')
                ->whereBetween('servicios_historico.fechaCreacion', [$fecha_desde, $fecha_hasta])
                ->where('relacion_tiposservicio_servicios_historico.tiposservicio_nombre', 'like', '%' . $tipificacion . '%') //para filtrar por tipificacion
                ->get();
            return response()->json(['servicios' => $servicios]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 200);
        }
    }

    public function getLlamadas(Request $request)
    {
        try {
            $fecha_desde = \Carbon\Carbon::parse($request->fecha_desde)->format('Y-m-d H:i:s');
            $fecha_hasta = \Carbon\Carbon::parse($request->fecha_hasta)->format('Y-m-d H:i:s');

            $llamadas = DB::connection('mysql_second')
                ->table('llamadas')
                ->select('llamadas.*', 'grabacionesoperador.rutaFichero', 'grabacionesoperador.nombreFichero')
                ->join('grabacionesoperador', 'llamadas.operadorInicio', '=', 'grabacionesoperador.operador')
                ->where('grabacionesoperador.nombreFichero', 'like', '%' . $request->telefono . '%')
                ->whereBetween('llamadas.fechaInicio', [$fecha_desde, $fecha_hasta])
                ->orderBy('llamadas.fechaInicio', 'DESC')
                ->distinct()
                ->get();

            return response()->json(['llamadas' => $llamadas]);
        } catch (\Exception $ex) {
            return response()->json(['status' => 'error', 'message' => $ex->getMessage()], 200);
        }
    }


    /*public function getLlamadas(Request $request)
    {
        //dd($request->all());
        try {
            $fecha_desde = \Carbon\Carbon::parse($request->fecha_desde)->format('Y-m-d H:i:s');
            $fecha_hasta = \Carbon\Carbon::parse($request->fecha_hasta)->format('Y-m-d H:i:s');
            $llamadas = DB::connection('mysql_second')
                ->table('llamadas')
                ->where('protocolo', $request->protocolo)
                ->where('numero', 'like', '%' . $request->telefono . '%')
                ->whereBetween('fechaInicio', [$fecha_desde, $fecha_hasta])
                ->orderBy('fechaInicio', 'DESC')
                ->get();
            return response()->json(['llamadas' => $llamadas]);
        } catch (\Exception $ex) {
            return response()->json(['status' => 'error', 'message' => $ex->getMessage()], 200);
        }
    }*/

    public function getRecorridosMoviles(Request $request)
    {
        request()->validate([
            'recursos' => 'required|not_in:Seleccionar recurso',
            'fecha_desde' => 'required',
            'fecha_hasta' => 'required',
        ], [
            'required' => 'El campo :attribute es necesario completar.'
        ]);
        try {
            $fecha_desde = \Carbon\Carbon::parse($request->fecha_desde)->format('Y-m-d H:i:s');
            $fecha_hasta = \Carbon\Carbon::parse($request->fecha_hasta)->format('Y-m-d H:i:s');
            $coordinates = [];
            $results = DB::connection('mysql_second')
                ->table('posicionesgps')
                ->whereIn('recurso', json_decode($request->recursos))
                ->whereBetween('fecha', [$fecha_desde, $fecha_hasta])
                //->whereRaw('SECOND(fecha) % 600 = 0') // Filtra las posiciones cada 10 minutos
                ->get()
                ->map(function ($result) use (&$coordinates) {
                    // Convertir las coordenadas de radianes a grados decimales
                    $result->latitud = round($result->latitud / 0.0174533, 7);
                    $result->longitud = round($result->longitud / 0.0174533, 7);

                    // Convertir la fecha al formato deseado
                    $result->fecha = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $result->fecha)->format('d/m/Y H:i:s');

                    $coordinates[] = ['lat' => $result->latitud, 'lng' => $result->longitud];
                    return $result;
                });


            // Agrupar los resultados por recurso
            $groupedResults = collect($results)->groupBy('recurso');
            // Estructura final de datos
            $finalData = [];
            // Iterar sobre los grupos y agregarlos a $finalData
            foreach ($groupedResults as $recurso => $group) {

                $data = [];

                foreach ($group as $result) {

                    // Realizar la geocodificación inversa
                    $result->direccion = $this->getDireccion($result->latitud, $result->longitud);
                    $data[] = [
                        'id' => $result->id,
                        'recurso' => $result->recurso,
                        'latitud' => $result->latitud,
                        'longitud' => $result->longitud,
                        'velocidad' => (float) $result->velocidad,
                        'fecha' => $result->fecha,
                        'direccion' => (($result->latitud == 0) && ($result->longitud == 0)) ? 'Dirección no encontrada' : $result->direccion,
                    ];
                }

                $finalData[] = [
                    'recurso' => $recurso,
                    'datos' => $data,
                ];
            }
            return response()->json(['moviles' => $finalData]);
        } catch (\Exception $ex) {
            return response()->json(['status' => 'error', 'message' => $ex->getMessage()], 200);
        }
    }

    public function obtenerIntervalosParado(Request $request)
    {
        //dd($request->all());
        $request->validate([
            'recursos' => 'required|string',
            'fecha_desde' => 'required',
            'fecha_hasta' => 'required',
            'tiempo_permitido' => 'required|numeric',
        ], [
            'required' => 'El campo :attribute es necesario completar.',
        ]);

        try {
            $recursos = json_decode($request->recursos, true);
            $tiempoPermitidoParar = $request->tiempo_permitido; //tiempo que se puede parar

            if (!is_array($recursos)) {
                throw new \Exception('Formato de recursos no válido.');
            }

            $fecha_desde = \Carbon\Carbon::parse($request->fecha_desde)->format('Y-m-d H:i:s');
            $fecha_hasta = \Carbon\Carbon::parse($request->fecha_hasta)->format('Y-m-d H:i:s');

            $intervalosParado = [];

            foreach ($recursos as $recurso) {
                $results = DB::connection('mysql_second')
                    ->table('posicionesgps')
                    ->where('recurso', trim($recurso))
                    ->whereBetween('fecha', [$fecha_desde, $fecha_hasta])
                    ->orderBy('fecha')
                    ->get();
                //dd($results);

                $prevPosition = null;
                $inicioParada = null;

                foreach ($results as $result) {
                    // Convertir las coordenadas de radianes a grados decimales
                    $result->latitud = round($result->latitud / 0.0174533, 7);
                    $result->longitud = round($result->longitud / 0.0174533, 7);

                    // Realizar la geocodificación inversa
                    $result->direccion = $this->getDireccion($result->latitud, $result->longitud);

                    if ($prevPosition) {
                        $distance = $this->calcularDistancia($prevPosition->latitud, $prevPosition->longitud, $result->latitud, $result->longitud);

                        if ($distance < 0.02) {
                            if (!$inicioParada) {
                                $inicioParada = \Carbon\Carbon::parse($result->fecha);
                            }
                        } else {
                            if ($inicioParada) {
                                $finParada = \Carbon\Carbon::parse($result->fecha);
                                $tiempoParado = $finParada->diffInMinutes($inicioParada);

                                if ($tiempoParado > $tiempoPermitidoParar) {
                                    $intervalosParado[] = [
                                        'recurso' => $recurso,
                                        'inicio_parado' => $inicioParada->format('H:i:s'),
                                        'fin_parado' => $finParada->format('H:i:s'),
                                        'latitud' => $prevPosition->latitud,
                                        'longitud' => $prevPosition->longitud,
                                        'lugar' => $this->getDireccion($prevPosition->latitud, $prevPosition->longitud),
                                        'tiempo_parado' => $tiempoParado,
                                    ];
                                }

                                $inicioParada = null;
                            }
                        }
                    }

                    $prevPosition = $result;
                }
            }
            //dd($intervalosParado);

            return response()->json(['intervalos_parado' => $intervalosParado]);
        } catch (\Exception $ex) {
            return response()->json(['status' => 'error', 'message' => $ex->getMessage()], 200);
        }
    }

    private function calcularDistancia($lat1, $lon1, $lat2, $lon2)
    {
        // Fórmula de Haversine para calcular la distancia entre dos puntos en la Tierra
        $lat1Rad = deg2rad($lat1);
        $lon1Rad = deg2rad($lon1);
        $lat2Rad = deg2rad($lat2);
        $lon2Rad = deg2rad($lon2);

        $deltaLat = $lat2Rad - $lat1Rad;
        $deltaLon = $lon2Rad - $lon1Rad;

        $a = sin($deltaLat / 2) * sin($deltaLat / 2) + cos($lat1Rad) * cos($lat2Rad) * sin($deltaLon / 2) * sin($deltaLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        $earthRadius = 6371; // Radio de la Tierra en kilómetros

        $distance = $earthRadius * $c;

        return $distance;
    }

    // Función para obtener la dirección mediante geocodificación inversa
    private function getDireccion($latitud, $longitud)
    {
        $geocoding = GeocodificacionInversa::where('latitud', round($latitud, 7))
            ->where('longitud', round($longitud, 7))
            ->first();

        if ($geocoding) {
            // Dirección encontrada en la tabla GeocodificacionInversa
            return $geocoding->direccion;
        } else {
            // Dirección no encontrada en la tabla, obtenerla a través de Google Maps
            $direccion = $this->getAddressGoogle($latitud, $longitud);

            if ($direccion != 'Dirección no encontrada') {
                // Guardar la dirección en la tabla GeocodificacionInversa para futuras consultas
                GeocodificacionInversa::create([
                    'latitud' => $latitud,
                    'longitud' => $longitud,
                    'direccion' => $direccion,
                ]);
            }
            return $direccion;
        }
    }


    /*public function getRecorridosMoviles(Request $request)
    {
        //set_time_limit(300); // Establece un tiempo máximo de ejecución de 150 segundos (2.5 minutos)
        try {
            $fecha_desde = \Carbon\Carbon::parse($request->fecha_desde)->format('Y-m-d H:i:s');
            $fecha_hasta = \Carbon\Carbon::parse($request->fecha_hasta)->format('Y-m-d H:i:s');
            $results = DB::connection('mysql_second')
                ->table('posicionesgps')
                ->where('recurso', $request->recurso)
                ->whereBetween('fecha', [$fecha_desde, $fecha_hasta])
                //->limit(50)
                ->get();

            $coordinates = [];

            foreach ($results as $result) {
                // Convertir las coordenadas de radianes a grados decimales
                $result->latitud = $result->latitud / 0.0174533;
                $result->longitud = $result->longitud / 0.0174533;
                $coordinates[] = ['lat' => $result->latitud, 'lng' => $result->longitud];
                // Convertir la fecha al formato deseado
                $result->fecha = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $result->fecha)->format('d/m/Y H:i:s');
            }

            // Obtener las direcciones en lotes
            $addresses = $this->getAddressesInBatchOpenStreetMap($coordinates);

            $data = [];
            foreach ($results as $key => $result) {
                // Agregar la dirección al resultado
                $result->direccion = isset($addresses[$key]['result']) ? $addresses[$key]['result'] : 'Dirección no encontrada';

                $data[] = [
                    'id' => $result->id,
                    'recurso' => $result->recurso,
                    'latitud' => $result->latitud,
                    'longitud' => $result->longitud,
                    'velocidad' => (float) $result->velocidad,
                    'fecha' => $result->fecha,
                    'direccion' => (($result->latitud == 0) && ($result->longitud == 0)) ? 'Dirección no encontrada' : $result->direccion,
                ];
            }

            return response()->json(["moviles" => $data]);
        } catch (\Exception $ex) {
            return response()->json(["status" => "error", "message" => $ex->getMessage()], 200);
        }
    }*/


    // Función para obtener la dirección a partir de las coordenadas
    private function getAddressGoogle($lat, $lng)
    {
        $apiKey = env('API_GOOGLE'); // Reemplaza con tu clave de API de Google Maps

        // Realizar solicitud a la API de geocodificación
        $url = "https://maps.googleapis.com/maps/api/geocode/json?latlng=$lat,$lng&key=$apiKey";
        $response = file_get_contents($url);
        $data = json_decode($response);

        if (isset($data->results[0]->formatted_address)) {
            return $data->results[0]->formatted_address;
        } else {
            return 'Dirección no encontrada';
        }
    }

    // Función para obtener la dirección a partir de las coordenadas usando cURL
    private function getAddress($lat, $lng)
    {
        $apiKey = env('API_ROUTE_SERVICE'); // Reemplaza con tu clave de API de OpenRouteService

        $url = "https://api.openrouteservice.org/geocode/reverse?api_key=$apiKey&point.lon=$lng&point.lat=$lat";

        $ch = curl_init($url);

        // Configura las opciones de cURL
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json; charset=utf-8',
            'Accept: application/json, application/geo+json, application/gpx+xml, img/png; charset=utf-8'
        ]);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);

        // Maneja la respuesta JSON
        if ($response) {
            $data = json_decode($response);

            if (isset($data->features[0]->properties->label)) {
                return $data->features[0]->properties->label;
            } else {
                return 'Dirección no encontrada';
            }
        } else {
            return 'Error en la solicitud de geocodificación';
        }

        curl_close($ch);
    }

    // Función para obtener direcciones en lotes utilizando cURL
    private function getAddressesInBatch($coordinates)
    {
        $apiKey = 'YOUR_API_ROUTE_SERVICE'; // Reemplaza con tu clave de API de OpenRouteService

        $batchSize = 10; // Número de coordenadas por lote
        $addresses = [];

        // Divide las coordenadas en lotes
        $coordinateChunks = array_chunk($coordinates, $batchSize);

        foreach ($coordinateChunks as $chunk) {
            $latitudes = array_column($chunk, 'lat');
            $longitudes = array_column($chunk, 'lng');
            $url = "https://api.openrouteservice.org/geocode/reverse?api_key=$apiKey&point.lat=" . implode(',', $latitudes) . "&point.lon=" . implode(',', $longitudes);

            $ch = curl_init($url);

            // Configura las opciones de cURL
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json; charset=utf-8',
                'Accept: application/json, application/geo+json, application/gpx+xml, img/png; charset=utf-8',
            ]);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $response = curl_exec($ch);

            if ($response) {
                $data = json_decode($response);

                foreach ($data->features as $feature) {
                    if (isset($feature->properties->label)) {
                        $addresses[] = ['result' => $feature->properties->label];
                    } else {
                        $addresses[] = ['result' => 'Dirección no encontrada'];
                    }
                }
            } else {
                // Manejar el error en la solicitud de geocodificación
                $addresses = array_fill(0, count($chunk), ['result' => 'Error en la solicitud de geocodificación']);
            }

            curl_close($ch);
        }

        return $addresses;
    }

    private function getAddressesInBatchGoogle($coordinates)
    {
        $apiKey = 'API_GOOGLE'; // Reemplaza con tu clave de API de Google

        $batchSize = 100; // Número de coordenadas por lote
        $addresses = [];

        // Divide las coordenadas en lotes
        $coordinateChunks = array_chunk($coordinates, $batchSize);

        foreach ($coordinateChunks as $chunk) {
            // Aquí, $chunk contiene un lote de coordenadas
            $batchAddresses = [];

            foreach ($chunk as $coordinate) {
                $lat = $coordinate['lat'];
                $lng = $coordinate['lng'];

                // Construir la URL de la solicitud a la API de Google Maps Geocoding
                $url = "https://maps.googleapis.com/maps/api/geocode/json?latlng=$lat,$lng&key=$apiKey";
                //usleep(1000000); // 1 segundo

                $ch = curl_init($url);

                // Configura las opciones de cURL
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                $response = curl_exec($ch);

                if ($response) {
                    $data = json_decode($response);

                    if ($data->status === 'OK' && isset($data->results[0]->formatted_address)) {
                        $batchAddresses[] = ['result' => $data->results[0]->formatted_address];
                    } else {
                        $batchAddresses[] = ['result' => 'Dirección no encontrada'];
                    }
                } else {
                    // Manejar el error en la solicitud de geocodificación
                    $batchAddresses[] = ['result' => 'Error en la solicitud de geocodificación'];
                }

                curl_close($ch);
            }

            // Agregar las direcciones del lote actual al arreglo de direcciones
            $addresses = array_merge($addresses, $batchAddresses);
        }

        return $addresses;
    }


    // Función para obtener la dirección a partir de las coordenadas usando cURL
    /*private function getAddress($lat, $lng)
    {
        $apiKey = '_API_ROUTE_SERVICE'; // Reemplaza con tu clave de API de OpenRouteService

        $url = "https://api.openrouteservice.org/geocode/reverse?api_key=$apiKey&point.lon=$lng&point.lat=$lat";

        $ch = curl_init($url);

        // Configura las opciones de cURL
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json; charset=utf-8',
            'Accept: application/json, application/geo+json, application/gpx+xml, img/png; charset=utf-8'
        ]);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);

        // Maneja la respuesta JSON
        if ($response) {
            $data = json_decode($response);

            if (isset($data->features[0]->properties->label)) {
                return $data->features[0]->properties->label;
            } else {
                return 'Dirección no encontrada';
            }
        } else {
            return 'Error en la solicitud de geocodificación';
        }

        curl_close($ch);
    }*/


    public function getEventos()
    {
    }
}
