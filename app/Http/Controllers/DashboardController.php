<?php

namespace App\Http\Controllers;

use App\Models\FlotaGeneral;
use Illuminate\Support\Facades\DB;
use App\Models\Recurso;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function getMovilesJSON(Request $request){
        /*fecha = $request->fecha;
        $desde = Carbon::createFromFormat('d/m/Y', $fecha)->startOfDay()->toDateTimeString();
        $hasta = Carbon::createFromFormat('d/m/Y', $fecha)->endOfDay()->toDateTimeString();*/


        $tipo_veh1 = 'Auto';
        $tipo_veh2 = 'Camioneta';
        /*$moviles = Recurso::whereHas('vehiculo', function ($query) use ($tipo_veh1, $tipo_veh2) {
            $query->where('tipo_vehiculo', '=', $tipo_veh1)->orWhere('tipo_vehiculo', '=', $tipo_veh2);
        })->get();*/

        $moviles = Recurso::select(
            'recursos.*',
            'vehiculos.tipo_vehiculo',
            DB::raw('recursos.nombre as nombre_recurso'),
            'destino.nombre'
        )
        ->leftJoin('vehiculos', 'recursos.vehiculo_id', 'vehiculos.id')
        ->leftJoin('destino', 'recursos.destino_id', 'destino.id')
        ->where('vehiculos.tipo_vehiculo', $tipo_veh1)
        ->orWhere('vehiculos.tipo_vehiculo', $tipo_veh2)
        ->get();


        //dd(response()->json($moviles));

        //dd($moviles->all());

        /*$logs = AuditoriaCelularHistorico::select(
                                          'auditoria_celulares_historico.*',
                                          DB::raw('ROUND(auditoria_celulares_historico.altitud,2) as altitud'),
                                          'distribuidor.apellido',
                                          'distribuidor.nombre',
                                          'sucursal.descripcion as sucursal',
                                          'zonas.nombre as zona',
                                          DB::raw('DATE_FORMAT(auditoria_celulares_historico.fecha, "%d/%m/%Y %H:%i") as fecha'),
                                          DB::raw('concat_ws("", distribuidor.apellido, " ", distribuidor.nombre) as distribuidor')
                                        )
                                ->leftJoin('distribuidor', 'auditoria_celulares_historico.distribuidor_id', 'distribuidor.id')
                                ->leftJoin('sucursal', 'sucursal.id', 'distribuidor.sucursal_id')
                                ->leftJoin('zonas', 'zonas.id', 'distribuidor.zona_id')
                                ->where('distribuidor.id', $request->distribuidor_id)
                                ->whereBetween('auditoria_celulares_historico.fecha', [$desde, $hasta])
                                ->orderBy('auditoria_celulares_historico.fecha')
                                ->get();*/
        return response()->json($moviles);
      }

      public function getMotosJSON(Request $request){
        $tipo_veh1 = 'Moto';

        $motos = Recurso::select(
            'recursos.*',
            'vehiculos.tipo_vehiculo',
            DB::raw('recursos.nombre as nombre_recurso'),
            'destino.nombre'
        )
        ->leftJoin('vehiculos', 'recursos.vehiculo_id', 'vehiculos.id')
        ->leftJoin('destino', 'recursos.destino_id', 'destino.id')
        ->where('vehiculos.tipo_vehiculo', $tipo_veh1)
        ->get();

        return response()->json($motos);
      }
}
