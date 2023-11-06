<?php

namespace App\Http\Controllers;

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
        //dd('entro');
        try {
            $results = DB::connection('mysql_second')
                ->table('posicionesgps')
                ->where('recurso', 'Cria 801')
                ->limit(50)->get();

            foreach ($results as $result) {
                // Convertir las coordenadas de radianes a grados decimales
                $result->latitud = $result->latitud / 0.0174533;
                $result->longitud = $result->longitud / 0.0174533;
                // Convertir la fecha al formato deseado
                $result->fecha = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $result->fecha)->format('d/m/Y H:i:s');
            }

            $data = [];
            foreach ($results as $result) {
                $data[] = [
                    'id' => $result->id,
                    'recurso' => $result->recurso,
                    'latitud' => $result->latitud,
                    'longitud' => $result->longitud,
                    'velocidad' => (float) $result->velocidad,
                    'fecha' => \Carbon\Carbon::createFromFormat('d/m/Y H:i:s', $result->fecha)->format('Y-m-d H:i:s'),
                ];
            }
            return response()->json(["moviles" => $data]);

        } catch (\Exception $ex) {
            //dd($ex);
            return response()->json(["status" => "error", "message" => $ex->getMessage()], 200);
        }

        // Convierte los resultados a JSON
        //$movil801 = json_encode($results);

        // Devuelve el JSON
        //return response()->json($movil801);
        //dd($results);

    }

    public function getEventos()
    {
    }
}
