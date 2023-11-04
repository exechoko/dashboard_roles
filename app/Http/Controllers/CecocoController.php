<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CecocoController extends Controller
{
    public function getLlamadas()
    {
        //dd('entro');
        $results = DB::connection('mysql_second')
        ->table('llamadas')
        ->where('protocolo', 5)
        ->limit(50)->get();
        dd($results);
    }

    public function getMoviles()
    {
        $results = DB::connection('mysql_second')
        ->table('posicionesgps')
        ->where('recurso', 'Cria 801')
        ->limit(50)->get();

        // Iterar a través de los resultados y convertir las coordenadas
        foreach ($results as $result) {
            // Convertir las coordenadas de radianes a grados decimales
            $result->latitud = $result->latitud / 0.0174533;
            $result->longitud = $result->longitud / 0.0174533;
        }
        dd($results);
        return 
        /*// Convierte los resultados a JSON
        $jsonResults = json_encode($results);

        // Devuelve el JSON
        return response()->json($jsonResults);*/
    }

    public function getEventos(){

    }
}
