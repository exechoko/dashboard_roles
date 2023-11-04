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
            // Convertir la fecha al formato deseado
            $result->fecha = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $result->fecha)->format('d/m/Y H:i:s');
        }

        // Convierte los resultados a JSON
        //$movil801 = json_encode($results);

        // Devuelve el JSON
        //return response()->json($movil801);
        //dd($results);
        return view('cecoco.moviles.index', compact('results'));

    }

    public function getEventos(){

    }
}
