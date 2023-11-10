<?php

namespace App\Http\Controllers;

use App\Models\GeocodificacionInversa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CecocoController extends Controller
{
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


    public function getLlamadas()
    {
        //dd('entro');
        $results = DB::connection('mysql_second')
            ->table('llamadas')
            ->where('protocolo', 5)
            ->limit(50)->get();
        dd($results);
    }

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
        $apiKey = 'YOUR_API'; // Reemplaza con tu clave de API de Google Maps

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
        $apiKey = 'API_ROUTE_SERVICE'; // Reemplaza con tu clave de API de OpenRouteService

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
