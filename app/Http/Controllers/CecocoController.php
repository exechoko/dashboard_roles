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
        ->get();
        dd($results->id);
    }

    public function getMoviles(){

    }

    public function getEventos(){

    }
}
