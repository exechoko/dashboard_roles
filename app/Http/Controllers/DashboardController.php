<?php

namespace App\Http\Controllers;

use App\Models\FlotaGeneral;
use Illuminate\Support\Facades\DB;
use App\Models\Recurso;
use App\Models\Destino;
use App\Models\Equipo;
use App\Models\Estado;
use App\Models\Historico;
use App\Models\TipoMovimiento;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function getCantidadEquiposSinFuncionarJSON(Request $request)
    {
        $records = Equipo::select(
            'tipo_terminales.marca as marca',
            'tipo_terminales.modelo as modelo',
            'equipos.provisto as provisto',
            DB::raw('COUNT(equipos.id) as cantidad')
        )
            ->leftJoin('tipo_terminales', 'equipos.tipo_terminal_id', '=', 'tipo_terminales.id')
            ->where('equipos.estado_id', 3) // Estado "no funciona"
            ->groupBy('tipo_terminales.id', 'tipo_terminales.marca', 'tipo_terminales.modelo', 'equipos.provisto')
            ->get();

        return response()->json($records);
    }

    public function getCantidadEquiposBajaJSON(Request $request)
    {
        $idEstadoBaja = Estado::where('nombre', 'Baja')->value('id');
        $idEstadoRecambio = Estado::where('nombre', 'Recambio')->value('id');
        $records = Equipo::select(
            'tipo_terminales.marca as marca',
            'tipo_terminales.modelo as modelo',
            'equipos.provisto as provisto',
            DB::raw('COUNT(equipos.id) as cantidad')
        )
            ->leftJoin('tipo_terminales', 'equipos.tipo_terminal_id', '=', 'tipo_terminales.id')
            ->whereIn('equipos.estado_id', [$idEstadoBaja, $idEstadoRecambio]) // Estado "Baja" "Recambio
            ->groupBy('tipo_terminales.id', 'tipo_terminales.marca', 'tipo_terminales.modelo', 'equipos.provisto')
            ->get();

        return response()->json($records);
    }

    /*public function getCantidadEquiposFuncionalesJSON(Request $request)
    {
        $idEstadoNuevo = Estado::where('nombre', 'Nuevo')->value('id');
        $idEstadoUsado = Estado::where('nombre', 'Usado')->value('id');
        $idEstadoReparado = Estado::where('nombre', 'Reparado')->value('id');
        $records = Equipo::select(
            'tipo_terminales.marca as marca',
            'tipo_terminales.modelo as modelo',
            'equipos.provisto as provisto',
            DB::raw('COUNT(equipos.id) as cantidad')
        )
            ->leftJoin('tipo_terminales', 'equipos.tipo_terminal_id', '=', 'tipo_terminales.id')
            ->whereIn('equipos.estado_id', [$idEstadoNuevo, $idEstadoUsado, $idEstadoReparado]) // Estados "Nuevo", "Usado" y "Reparado"
            ->groupBy('tipo_terminales.id', 'equipos.provisto')
            ->get();

        return response()->json($records);
    }*/
    public function getCantidadEquiposFuncionalesJSON(Request $request)
    {
        $stock911 = Recurso::where('nombre', 'Stock 911')->first();

        $idEstadoNuevo = Estado::where('nombre', 'Nuevo')->value('id');
        $idEstadoUsado = Estado::where('nombre', 'Usado')->value('id');
        $idEstadoReparado = Estado::where('nombre', 'Reparado')->value('id');

        $records = Equipo::select(
            'tipo_terminales.marca as marca',
            'tipo_terminales.modelo as modelo',
            'equipos.provisto as provisto',
            'tipo_uso.uso as categoria',
            DB::raw('COUNT(equipos.id) as cantidad'),
            DB::raw('SUM(CASE WHEN flota_general.recurso_id = ' . $stock911->id . ' THEN 1 ELSE 0 END) as cantidad_en_stock'),
            DB::raw('SUM(CASE WHEN flota_general.recurso_id IS NULL OR flota_general.recurso_id != ' . $stock911->id . ' THEN 1 ELSE 0 END) as cantidad_en_uso')
        )
            ->leftJoin('tipo_terminales', 'equipos.tipo_terminal_id', '=', 'tipo_terminales.id')
            ->leftJoin('tipo_uso', 'tipo_terminales.tipo_uso_id', '=', 'tipo_uso.id')
            ->leftJoin('flota_general', 'equipos.id', '=', 'flota_general.equipo_id')
            ->whereIn('equipos.estado_id', [$idEstadoNuevo, $idEstadoUsado, $idEstadoReparado])
            ->groupBy(
                'tipo_terminales.id',
                'tipo_terminales.marca',
                'tipo_terminales.modelo',
                'tipo_uso.uso',
                'equipos.provisto'
            )
            ->orderBy('tipo_terminales.marca', 'DESC')
            ->orderBy('tipo_terminales.modelo', 'DESC')
            ->get();

        return response()->json($records);
    }

    public function getCantidadEquiposProvistosPorPGJSON(Request $request)
    {
        // Obtener cantidades agrupadas por estado
        $recordsPorEstado = Equipo::select(
            'tipo_terminales.marca as marca',
            'tipo_terminales.modelo as modelo',
            'estados.nombre as estado',
            'equipos.provisto as provisto',
            DB::raw('COUNT(equipos.id) as cantidad')
        )
            ->leftJoin('tipo_terminales', 'equipos.tipo_terminal_id', '=', 'tipo_terminales.id')
            ->leftJoin('estados', 'equipos.estado_id', '=', 'estados.id')
            ->where('equipos.provisto', 'Patagonia Green')
            ->groupBy('tipo_terminales.id', 'tipo_terminales.marca', 'tipo_terminales.modelo', 'equipos.estado_id', 'equipos.provisto', 'estados.nombre')
            ->orderBy('tipo_terminales.marca')
            ->orderBy('tipo_terminales.modelo')
            ->get();

        // Obtener cantidades totales sin importar el estado
        $recordsTotales = Equipo::select(
            'tipo_terminales.marca as marca',
            'tipo_terminales.modelo as modelo',
            'equipos.provisto as provisto',
            DB::raw('COUNT(equipos.id) as cantidad')
        )
            ->leftJoin('tipo_terminales', 'equipos.tipo_terminal_id', '=', 'tipo_terminales.id')
            ->where('equipos.provisto', 'Patagonia Green')
            ->groupBy('tipo_terminales.id', 'tipo_terminales.marca', 'tipo_terminales.modelo', 'equipos.provisto')
            ->orderBy('tipo_terminales.marca')
            ->orderBy('tipo_terminales.modelo')
            ->get();

        // Combinar los resultados y devolver como respuesta JSON
        $mergedRecords = $recordsPorEstado->merge($recordsTotales);

        return response()->json(['records' => $recordsPorEstado, 'recordsTotales' => $recordsTotales]);
    }

    public function getCantidadEquiposProvistosPorTELECOMJSON(Request $request)
    {
        // Obtener cantidades agrupadas por estado
        $recordsPorEstado = Equipo::select(
            'tipo_terminales.marca as marca',
            'tipo_terminales.modelo as modelo',
            'estados.nombre as estado',
            'equipos.provisto as provisto',
            DB::raw('COUNT(equipos.id) as cantidad')
        )
            ->leftJoin('tipo_terminales', 'equipos.tipo_terminal_id', '=', 'tipo_terminales.id')
            ->leftJoin('estados', 'equipos.estado_id', '=', 'estados.id')
            ->where('equipos.provisto', 'Telecom')
            ->groupBy('tipo_terminales.id', 'tipo_terminales.marca', 'tipo_terminales.modelo', 'equipos.estado_id', 'equipos.provisto', 'estados.nombre')
            ->orderBy('tipo_terminales.marca')
            ->orderBy('tipo_terminales.modelo')
            ->get();

        // Obtener cantidades totales sin importar el estado
        $recordsTotales = Equipo::select(
            'tipo_terminales.marca as marca',
            'tipo_terminales.modelo as modelo',
            'equipos.provisto as provisto',
            DB::raw('COUNT(equipos.id) as cantidad')
        )
            ->leftJoin('tipo_terminales', 'equipos.tipo_terminal_id', '=', 'tipo_terminales.id')
            ->where('equipos.provisto', 'Telecom')
            ->groupBy('tipo_terminales.id', 'tipo_terminales.marca', 'tipo_terminales.modelo', 'equipos.provisto')
            ->orderBy('tipo_terminales.marca')
            ->orderBy('tipo_terminales.modelo')
            ->get();

        // Combinar los resultados y devolver como respuesta JSON
        $mergedRecords = $recordsPorEstado->merge($recordsTotales);

        return response()->json(['records' => $recordsPorEstado, 'recordsTotales' => $recordsTotales]);
    }


    public function getDesinstalacionesParcialesJSON(Request $request)
    {
        $records = Historico::select(
            'historico.*',
            //DB::raw("DATE_FORMAT(historico.created_at, '%d-%m-%Y %H:%i:%s') as fecha"),
            DB::raw("DATE_FORMAT(historico.fecha_asignacion, '%d-%m-%Y') as fecha"),
            'equipos.tei as tei',
            'equipos.issi as issi'

        )
            ->leftJoin('equipos', 'historico.equipo_id', '=', 'equipos.id')
            ->where('historico.id', function ($query) {
                $query->select(DB::raw('MAX(h2.id)'))
                    ->from('historico as h2')
                    ->whereRaw('h2.equipo_id = historico.equipo_id')
                    ->groupBy('h2.equipo_id');
            })
            ->where('historico.tipo_movimiento_id', function ($subquery) {
                $subquery->select('id')
                    ->from('tipo_movimiento')
                    ->where('nombre', 'Desinstalación Parcial');
            })
            ->get();
        //dd(response()->json($records));
        return response()->json($records);
    }

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
            DB::raw('DATE_FORMAT(historico.fecha_asignacion, "%d-%m-%Y %H:%i") as fecha'),
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

    public function getCantidadEquiposEnPGJSON(Request $request)
    {
        $dest_pg = Destino::where('nombre', 'Patagonia Green')->first();

        if (!$dest_pg) {
            return response()->json(['error' => 'Destino Patagonia Green no encontrado'], 404);
        }

        $cantidad_equipos_en_pg = Historico::where('destino_id', $dest_pg->id)
            ->where('fecha_desasignacion', null)
            ->count();

        return response()->json(['cantidad_equipos_en_pg' => $cantidad_equipos_en_pg]);
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

    public function getEquiposEnStockJSON(Request $request)
    {
        $stock911 = Recurso::where('nombre', 'Stock 911')->first();

        // Verifica si se encontró el recurso 'Stock 911'
        if (!$stock911) {
            return response()->json(['error' => 'Recurso Stock 911 no encontrado'], 404);
        }

        $equipos_en_stock = FlotaGeneral::select(
            'flota_general.*',
            'equipos.*',
            'historico.*',
            'tipo_terminales.marca',
            'tipo_terminales.modelo',
            'recursos.nombre',
            DB::raw('DATE_FORMAT(historico.fecha_asignacion, "%d-%m-%Y %H:%i") as fecha'),
            DB::raw('equipos.tei as tei'),
            DB::raw('equipos.issi as issi'),
            DB::raw('tipo_terminales.marca as marca'),
            DB::raw('tipo_terminales.modelo as modelo'),
            DB::raw('recursos.nombre as nombre_recurso'),
            DB::raw('historico.recurso_desasignado as recurso_anterior'),
        )
            ->leftJoin('equipos', 'flota_general.equipo_id', 'equipos.id')
            ->leftJoin('historico', 'flota_general.equipo_id', 'historico.equipo_id')
            ->leftJoin('tipo_terminales', 'equipos.tipo_terminal_id', 'tipo_terminales.id')
            ->leftJoin('recursos', 'flota_general.recurso_id', 'recursos.id')
            ->where('flota_general.recurso_id', $stock911->id)
            ->where('flota_general.destino_id', $stock911->destino_id)
            ->where('historico.fecha_desasignacion', null)
            ->whereHas('equipo', function ($query) {
                $query->where('estado_id', '!=', 3);
            })
            ->orderBy('historico.id', 'desc')
            ->get();

        return response()->json($equipos_en_stock);
    }

    public function getCantidadEquiposEnStockJSON(Request $request)
    {
        $stock911 = Recurso::where('nombre', 'Stock 911')->first();

        // Verifica si se encontró el recurso 'Stock 911'
        if (!$stock911) {
            return response()->json(['error' => 'Recurso Stock 911 no encontrado'], 404);
        }

        $cantidad_equipos_en_stock = FlotaGeneral::where('recurso_id', $stock911->id)
            ->where('destino_id', $stock911->destino_id)
            ->where('fecha_desasignacion', null)
            ->whereHas('equipo', function ($query) {
                $query->where('estado_id', '!=', 3);
            })
            ->count();

        return response()->json(['cantidad_equipos_en_stock' => $cantidad_equipos_en_stock]);
    }

    public function getEquiposPorDepartamentalJSON(Request $request)
    {
        $departamentalParana = Destino::where('nombre', 'Departamental Paraná (JDP)')->first();

        if (!$departamentalParana) {
            return response()->json(['error' => 'Departamental Parná no encontrada'], 404);
        }

        $equipos_por_departamental = FlotaGeneral::select(
            'flota_general.*',
            'equipos.*',
            'historico.*',
            'tipo_terminales.marca',
            'tipo_terminales.modelo',
            'recursos.nombre',
            DB::raw('DATE_FORMAT(historico.fecha_asignacion, "%d-%m-%Y %H:%i") as fecha'),
            DB::raw('equipos.tei as tei'),
            DB::raw('equipos.issi as issi'),
            DB::raw('tipo_terminales.marca as marca'),
            DB::raw('tipo_terminales.modelo as modelo'),
            DB::raw('recursos.nombre as nombre_recurso'),
            DB::raw('historico.recurso_desasignado as recurso_anterior'),
        )
            ->leftJoin('equipos', 'flota_general.equipo_id', 'equipos.id')
            ->leftJoin('historico', 'flota_general.equipo_id', 'historico.equipo_id')
            ->leftJoin('tipo_terminales', 'equipos.tipo_terminal_id', 'tipo_terminales.id')
            ->leftJoin('recursos', 'flota_general.recurso_id', 'recursos.id')
            ->whereHas('destino', function ($query) use ($departamentalParana) {
                $query->where('departamental_id', $departamentalParana->departamental_id);
            })
            ->where('historico.fecha_desasignacion', null)
            ->where('equipos.estado_id', '!=', 3) // Agregando la condición de estado_id distinto a 3
            ->orderBy('historico.id', 'desc')
            ->get();

        return response()->json($equipos_por_departamental);
    }

    public function getCantidadEquiposEnDepartamentalJSON(Request $request)
    {
        $departamentalParana = Destino::where('nombre', 'Departamental Paraná (JDP)')->first();

        if (!$departamentalParana) {
            return response()->json(['error' => 'Departamental Paraná no encontrada'], 404);
        }

        $cantidad_equipos_en_departamental = FlotaGeneral::whereHas('destino', function ($query) use ($departamentalParana) {
            $query->where('departamental_id', $departamentalParana->departamental_id);
        })
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('historico')
                    ->whereRaw('flota_general.equipo_id = historico.equipo_id')
                    ->where('historico.fecha_desasignacion', null);
            })
            ->whereHas('equipo', function ($query) {
                $query->where('estado_id', '!=', 3);
            })
            ->count();

        return response()->json(['cantidad_equipos_en_departamental' => $cantidad_equipos_en_departamental]);
    }

    public function getEquiposDivision911JSON(Request $request)
    {
        $division911 = Destino::where('nombre', 'División 911 y Videovigilancia')->first();

        if (!$division911) {
            return response()->json(['error' => 'División 911 no encontrada'], 404);
        }

        $equiposEnDivision911 = FlotaGeneral::select(
            'flota_general.*',
            'equipos.*',
            'historico.*',
            'tipo_terminales.marca',
            'tipo_terminales.modelo',
            'recursos.nombre',
            DB::raw('DATE_FORMAT(historico.fecha_asignacion, "%d-%m-%Y %H:%i") as fecha'),
            DB::raw('equipos.tei as tei'),
            DB::raw('equipos.issi as issi'),
            DB::raw('tipo_terminales.marca as marca'),
            DB::raw('tipo_terminales.modelo as modelo'),
            DB::raw('recursos.nombre as nombre_recurso'),
            DB::raw('historico.recurso_desasignado as recurso_anterior'),
        )
            ->leftJoin('equipos', 'flota_general.equipo_id', 'equipos.id')
            ->leftJoin('historico', 'flota_general.equipo_id', 'historico.equipo_id')
            ->leftJoin('tipo_terminales', 'equipos.tipo_terminal_id', 'tipo_terminales.id')
            ->leftJoin('recursos', 'flota_general.recurso_id', 'recursos.id')
            ->whereHas('destino', function ($query) use ($division911) {
                $query->where('division_id', $division911->division_id);
            })
            ->where('historico.fecha_desasignacion', null)
            ->where('equipos.estado_id', '!=', 3) // Agregando la condición de estado_id distinto a 3
            ->orderBy('historico.id', 'desc')
            ->get();

        return response()->json($equiposEnDivision911);
    }

    public function getCantidadEquiposEnDivision911JSON(Request $request)
    {
        $division911 = Destino::where('nombre', 'División 911 y Videovigilancia')->first();

        if (!$division911) {
            return response()->json(['error' => 'División 911 no encontrada'], 404);
        }

        $cantidad_equipos_en_div_911 = FlotaGeneral::whereHas('destino', function ($query) use ($division911) {
            $query->where('division_id', $division911->division_id);
        })
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('historico')
                    ->whereRaw('flota_general.equipo_id = historico.equipo_id')
                    ->where('historico.fecha_desasignacion', null);
            })
            ->whereHas('equipo', function ($query) {
                $query->where('estado_id', '!=', 3);
            })
            ->count();

        return response()->json(['cantidad_equipos_en_division_911' => $cantidad_equipos_en_div_911]);
    }

    public function getEquiposDivisionBancariaJSON(Request $request)
    {
        $divisionBancaria = Destino::where('nombre', 'División Seguridad Urbana y Bancaria')->first();

        if (!$divisionBancaria) {
            return response()->json(['error' => 'División Bancaria no encontrada'], 404);
        }

        $equiposEnDivisionBancaria = FlotaGeneral::select(
            'flota_general.*',
            'equipos.*',
            'historico.*',
            'tipo_terminales.marca',
            'tipo_terminales.modelo',
            'recursos.nombre',
            DB::raw('DATE_FORMAT(historico.fecha_asignacion, "%d-%m-%Y %H:%i") as fecha'),
            DB::raw('equipos.tei as tei'),
            DB::raw('equipos.issi as issi'),
            DB::raw('tipo_terminales.marca as marca'),
            DB::raw('tipo_terminales.modelo as modelo'),
            DB::raw('recursos.nombre as nombre_recurso'),
            DB::raw('historico.recurso_desasignado as recurso_anterior'),
        )
            ->leftJoin('equipos', 'flota_general.equipo_id', 'equipos.id')
            ->leftJoin('historico', 'flota_general.equipo_id', 'historico.equipo_id')
            ->leftJoin('tipo_terminales', 'equipos.tipo_terminal_id', 'tipo_terminales.id')
            ->leftJoin('recursos', 'flota_general.recurso_id', 'recursos.id')
            ->whereHas('destino', function ($query) use ($divisionBancaria) {
                $query->where('division_id', $divisionBancaria->division_id);
            })
            ->where('historico.fecha_desasignacion', null)
            ->where('equipos.estado_id', '!=', 3) // Agregando la condición de estado_id distinto a 3
            ->orderBy('historico.id', 'desc')
            ->get();

        return response()->json($equiposEnDivisionBancaria);
    }

    public function getCantidadEquiposEnDivisionBancariaJSON(Request $request)
    {
        $divisionBancaria = Destino::where('nombre', 'División Seguridad Urbana y Bancaria')->first();

        if (!$divisionBancaria) {
            return response()->json(['error' => 'División Bancaria no encontrada'], 404);
        }

        $cantidad_equipos_en_div_bancaria = FlotaGeneral::whereHas('destino', function ($query) use ($divisionBancaria) {
            $query->where('division_id', $divisionBancaria->division_id);
        })
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('historico')
                    ->whereRaw('flota_general.equipo_id = historico.equipo_id')
                    ->where('historico.fecha_desasignacion', null);
            })
            ->whereHas('equipo', function ($query) {
                $query->where('estado_id', '!=', 3);
            })
            ->count();

        return response()->json(['cantidad_equipos_en_division_bancaria' => $cantidad_equipos_en_div_bancaria]);
    }

    public function getCantidadDesinstalacionesParcialesJSON(Request $request)
    {
        $cant_desinstalaciones = Historico::whereIn('id', function ($query) {
            $query
                ->select(DB::raw('MAX(id)'))
                ->from('historico')
                ->groupBy('equipo_id');
        })
            ->where('tipo_movimiento_id', function ($subquery) {
                $subquery
                    ->select('id')
                    ->from('tipo_movimiento')
                    ->where('nombre', 'Desinstalación Parcial');
            })
            ->count();
        return response()->json(['cantidad_desinstalaciones_parciales' => $cant_desinstalaciones]);
    }
}
