<?php

namespace App\Http\Controllers;

use App\Models\FlotaGeneral;
use Illuminate\Support\Facades\DB;
use App\Models\Recurso;
use App\Models\Destino;
use App\Models\Equipo;
use App\Models\Historico;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function getMovilesJSON(Request $request)
    {
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

    public function getMotosJSON(Request $request)
    {
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

    public function getEquiposPgJSON(Request $request)
    {
        //dd('aca');
        $dest_pg = Destino::where('nombre', 'Patagonia Green')->first();

        $equipos_en_pg = Historico::select(
            'historico.*',
            'equipos.tipo_terminal_id',
            'equipos.issi',
            'equipos.tei',
            'tipo_terminales.marca',
            'tipo_terminales.modelo',
            'recursos.nombre',
            DB::raw('equipos.tei as tei'),
            DB::raw('equipos.issi as issi'),
            DB::raw('tipo_terminales.marca as marca'),
            DB::raw('tipo_terminales.modelo as modelo'),
            DB::raw('recursos.nombre as nombre_recurso'),
        )
            ->leftJoin('equipos', 'historico.equipo_id', 'equipos.id')
            ->leftJoin('tipo_terminales', 'equipos.tipo_terminal_id', 'tipo_terminales.id')
            ->leftJoin('recursos', 'historico.recurso_id', 'recursos.id')
            ->where('historico.destino_id', $dest_pg->id)
            ->where('historico.fecha_desasignacion', null)
            ->get();

        //dd(response()->json($equipos_en_pg));
        return response()->json($equipos_en_pg);
    }

    public function getEquiposPorDependenciaJSON(Request $request)
    {
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
