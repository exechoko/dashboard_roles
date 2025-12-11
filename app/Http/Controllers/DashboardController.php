<?php

namespace App\Http\Controllers;

use App\Models\FlotaGeneral;
use Illuminate\Support\Facades\DB;
use App\Models\Recurso;
use App\Models\Destino;
use App\Models\Equipo;
use App\Models\Estado;
use App\Models\Historico;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    // Cache de IDs de estados para evitar consultas repetidas
    private $estadosCache = null;

    /**
     * Obtiene los IDs de estados cacheados
     */
    private function getEstadosIds()
    {
        if ($this->estadosCache === null) {
            $this->estadosCache = Estado::whereIn('nombre', [
                'Nuevo',
                'Usado',
                'Reparado',
                'Baja',
                'No funciona',
                'Perdido',
                'Recambio',
                'Temporal',
                'En revision'
            ])->pluck('id', 'nombre');
        }
        return $this->estadosCache;
    }

    /**
     * Query base para equipos funcionales
     */
    private function equiposFuncionalesQuery()
    {
        $estados = $this->getEstadosIds();

        return Equipo::select(
            'tipo_terminales.marca as marca',
            'tipo_terminales.modelo as modelo',
            'equipos.provisto as provisto',
            'tipo_uso.uso as categoria'
        )
            ->leftJoin('tipo_terminales', 'equipos.tipo_terminal_id', '=', 'tipo_terminales.id')
            ->leftJoin('tipo_uso', 'tipo_terminales.tipo_uso_id', '=', 'tipo_uso.id')
            ->whereIn('equipos.estado_id', [
                $estados['Nuevo'],
                $estados['Usado'],
                $estados['Reparado']
            ]);
    }

    public function getEquiposEnRevisionJSON(Request $request)
    {
        $estados = $this->getEstadosIds();

        // Validar que el estado "En revision" exista en la tabla estados
        if (!isset($estados['En revision'])) {
            return response()->json(['error' => 'Estado "En revision" no encontrado'], 404);
        }

        $records = Equipo::select(
            'tipo_terminales.marca as marca',
            'tipo_terminales.modelo as modelo',
            'equipos.provisto as provisto',
            'tipo_uso.uso as categoria',
            DB::raw('COUNT(equipos.id) as cantidad')
        )
            ->leftJoin('tipo_terminales', 'equipos.tipo_terminal_id', '=', 'tipo_terminales.id')
            ->leftJoin('tipo_uso', 'tipo_terminales.tipo_uso_id', '=', 'tipo_uso.id')
            ->where('equipos.estado_id', $estados['En revision'])
            ->groupBy('tipo_terminales.id', 'tipo_terminales.marca', 'tipo_terminales.modelo', 'equipos.provisto')
            ->orderBy('tipo_terminales.marca')
            ->orderBy('tipo_terminales.modelo')
            ->get();

        return response()->json($records);
    }


    /**
     * Query base para equipos por destino/división
     */
    private function equiposPorDestinoQuery($destinoNombre, $tipoBusqueda = 'destino')
    {
        $estados = $this->getEstadosIds();
        $destino = Destino::where('nombre', $destinoNombre)->first();

        if (!$destino) {
            return null;
        }

        $query = FlotaGeneral::select(
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
            DB::raw('historico.recurso_desasignado as recurso_anterior')
        )
            ->leftJoin('equipos', 'flota_general.equipo_id', 'equipos.id')
            ->leftJoin('historico', 'flota_general.equipo_id', 'historico.equipo_id')
            ->leftJoin('tipo_terminales', 'equipos.tipo_terminal_id', 'tipo_terminales.id')
            ->leftJoin('recursos', 'flota_general.recurso_id', 'recursos.id')
            ->where('historico.fecha_desasignacion', null)
            ->where('equipos.estado_id', '!=', $estados['No funciona'])
            ->orderBy('historico.id', 'desc');

        // Aplicar filtro según tipo de búsqueda
        if ($tipoBusqueda === 'departamental') {
            $query->whereHas(
                'destino',
                fn($q) =>
                $q->where('departamental_id', $destino->departamental_id)
            );
        } elseif ($tipoBusqueda === 'division') {
            $query->whereHas(
                'destino',
                fn($q) =>
                $q->where('division_id', $destino->division_id)
            );
        }

        return $query;
    }

    public function getCantidadEquiposSinFuncionarJSON(Request $request)
    {
        $estados = $this->getEstadosIds();

        $records = Equipo::select(
            'tipo_terminales.marca as marca',
            'tipo_terminales.modelo as modelo',
            'equipos.provisto as provisto',
            DB::raw('COUNT(equipos.id) as cantidad')
        )
            ->leftJoin('tipo_terminales', 'equipos.tipo_terminal_id', '=', 'tipo_terminales.id')
            ->where('equipos.estado_id', $estados['No funciona'])
            ->groupBy('tipo_terminales.id', 'tipo_terminales.marca', 'tipo_terminales.modelo', 'equipos.provisto')
            ->get();

        return response()->json($records);
    }

    public function getCantidadEquiposBajaJSON(Request $request)
    {
        $estados = $this->getEstadosIds();

        $records = Equipo::select(
            'tipo_terminales.marca as marca',
            'tipo_terminales.modelo as modelo',
            'equipos.provisto as provisto',
            DB::raw('COUNT(equipos.id) as cantidad')
        )
            ->leftJoin('tipo_terminales', 'equipos.tipo_terminal_id', '=', 'tipo_terminales.id')
            ->whereIn('equipos.estado_id', [
                $estados['Baja'],
                $estados['Recambio'],
                $estados['Perdido']
            ])
            ->groupBy('tipo_terminales.id', 'tipo_terminales.marca', 'tipo_terminales.modelo', 'equipos.provisto')
            ->get();

        return response()->json($records);
    }

    public function getCantidadEquiposFuncionalesJSON(Request $request)
    {
        $stock911 = Recurso::where('nombre', 'Stock 911')->first();

        if (!$stock911) {
            return response()->json(['error' => 'Recurso Stock 911 no encontrado'], 404);
        }

        $records = $this->equiposFuncionalesQuery()
            ->addSelect([
                DB::raw('COUNT(equipos.id) as cantidad'),
                DB::raw('SUM(CASE WHEN flota_general.recurso_id = ' . $stock911->id . ' THEN 1 ELSE 0 END) as cantidad_en_stock'),
                DB::raw('SUM(CASE WHEN flota_general.recurso_id IS NULL OR flota_general.recurso_id != ' . $stock911->id . ' THEN 1 ELSE 0 END) as cantidad_en_uso')
            ])
            ->leftJoin('flota_general', 'equipos.id', '=', 'flota_general.equipo_id')
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
        return $this->getCantidadEquiposProvistosPorProveedorJSON('Patagonia Green');
    }

    public function getCantidadEquiposProvistosPorTELECOMJSON(Request $request)
    {
        return $this->getCantidadEquiposProvistosPorProveedorJSON('Telecom');
    }

    public function getCantidadEquiposProvistosPorPERJSON(Request $request)
    {
        return $this->getCantidadEquiposProvistosPorProveedorJSON('Policía de Entre Ríos');
    }

    /**
     * Método genérico para obtener equipos por proveedor
     */
    private function getCantidadEquiposProvistosPorProveedorJSON($proveedor)
    {
        // Equipos agrupados por estado
        $recordsPorEstado = Equipo::select(
            'tipo_terminales.marca as marca',
            'tipo_terminales.modelo as modelo',
            'estados.nombre as estado',
            'equipos.provisto as provisto',
            DB::raw('COUNT(equipos.id) as cantidad')
        )
            ->leftJoin('tipo_terminales', 'equipos.tipo_terminal_id', '=', 'tipo_terminales.id')
            ->leftJoin('estados', 'equipos.estado_id', '=', 'estados.id')
            ->where('equipos.provisto', $proveedor)
            ->groupBy(
                'tipo_terminales.id',
                'tipo_terminales.marca',
                'tipo_terminales.modelo',
                'equipos.estado_id',
                'equipos.provisto',
                'estados.nombre'
            )
            ->orderBy('tipo_terminales.marca')
            ->orderBy('tipo_terminales.modelo')
            ->get();

        // Totales sin importar el estado
        $recordsTotales = Equipo::select(
            'tipo_terminales.marca as marca',
            'tipo_terminales.modelo as modelo',
            'equipos.provisto as provisto',
            DB::raw('COUNT(equipos.id) as cantidad')
        )
            ->leftJoin('tipo_terminales', 'equipos.tipo_terminal_id', '=', 'tipo_terminales.id')
            ->where('equipos.provisto', $proveedor)
            ->groupBy('tipo_terminales.id', 'tipo_terminales.marca', 'tipo_terminales.modelo', 'equipos.provisto')
            ->orderBy('tipo_terminales.marca')
            ->orderBy('tipo_terminales.modelo')
            ->get();

        return response()->json([
            'records' => $recordsPorEstado,
            'recordsTotales' => $recordsTotales
        ]);
    }

    public function getDesinstalacionesParcialesJSON(Request $request)
    {
        $records = Historico::select(
            'historico.*',
            DB::raw("DATE_FORMAT(historico.fecha_asignacion, '%d-%m-%Y') as fecha"),
            'equipos.tei as tei',
            'equipos.issi as issi'
        )
            ->leftJoin('equipos', 'historico.equipo_id', '=', 'equipos.id')
            ->whereIn('historico.id', function ($query) {
                $query->select(DB::raw('MAX(h2.id)'))
                    ->from('historico as h2')
                    ->whereRaw('h2.equipo_id = historico.equipo_id')
                    ->groupBy('h2.equipo_id');
            })
            ->whereIn('historico.tipo_movimiento_id', function ($subquery) {
                $subquery->select('id')
                    ->from('tipo_movimiento')
                    ->where('nombre', 'Desinstalación Parcial');
            })
            ->get();

        return response()->json($records);
    }

    public function getMovilesJSON(Request $request)
    {
        $moviles = Recurso::select(
            'recursos.*',
            'vehiculos.tipo_vehiculo',
            DB::raw('recursos.nombre as nombre_recurso'),
            'destino.nombre'
        )
            ->leftJoin('vehiculos', 'recursos.vehiculo_id', 'vehiculos.id')
            ->leftJoin('destino', 'recursos.destino_id', 'destino.id')
            ->whereIn('vehiculos.tipo_vehiculo', ['Auto', 'Camioneta'])
            ->get();

        return response()->json($moviles);
    }

    public function getMotosJSON(Request $request)
    {
        $motos = Recurso::select(
            'recursos.*',
            'vehiculos.tipo_vehiculo',
            DB::raw('recursos.nombre as nombre_recurso'),
            'destino.nombre'
        )
            ->leftJoin('vehiculos', 'recursos.vehiculo_id', 'vehiculos.id')
            ->leftJoin('destino', 'recursos.destino_id', 'destino.id')
            ->where('vehiculos.tipo_vehiculo', 'Moto')
            ->get();

        return response()->json($motos);
    }

    public function getEquiposPgJSON(Request $request)
    {
        $destPg = Destino::where('nombre', 'Patagonia Green')->first();

        if (!$destPg) {
            return response()->json(['error' => 'Destino Patagonia Green no encontrado'], 404);
        }

        $equiposEnPg = Historico::select(
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
            DB::raw('recursos.nombre as nombre_recurso')
        )
            ->leftJoin('equipos', 'historico.equipo_id', 'equipos.id')
            ->leftJoin('tipo_terminales', 'equipos.tipo_terminal_id', 'tipo_terminales.id')
            ->leftJoin('recursos', 'historico.recurso_id', 'recursos.id')
            ->where('historico.destino_id', $destPg->id)
            ->whereNull('historico.fecha_desasignacion')
            ->get();

        return response()->json($equiposEnPg);
    }

    public function getCantidadEquiposEnPGJSON(Request $request)
    {
        $destPg = Destino::where('nombre', 'Patagonia Green')->first();

        if (!$destPg) {
            return response()->json(['error' => 'Destino Patagonia Green no encontrado'], 404);
        }

        $cantidad = Historico::where('destino_id', $destPg->id)
            ->whereNull('fecha_desasignacion')
            ->count();

        return response()->json(['cantidad_equipos_en_pg' => $cantidad]);
    }

    public function getEquiposEnStockJSON(Request $request)
    {
        $stock911 = Recurso::where('nombre', 'Stock 911')->first();

        if (!$stock911) {
            return response()->json(['error' => 'Recurso Stock 911 no encontrado'], 404);
        }

        $estados = $this->getEstadosIds();

        $equiposEnStock = FlotaGeneral::select(
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
            DB::raw('historico.recurso_desasignado as recurso_anterior')
        )
            ->leftJoin('equipos', 'flota_general.equipo_id', 'equipos.id')
            ->leftJoin('historico', 'flota_general.equipo_id', 'historico.equipo_id')
            ->leftJoin('tipo_terminales', 'equipos.tipo_terminal_id', 'tipo_terminales.id')
            ->leftJoin('recursos', 'flota_general.recurso_id', 'recursos.id')
            ->where('flota_general.recurso_id', $stock911->id)
            ->where('flota_general.destino_id', $stock911->destino_id)
            ->whereNull('historico.fecha_desasignacion')
            ->whereHas('equipo', fn($q) => $q->where('estado_id', '!=', $estados['No funciona']))
            ->orderBy('historico.id', 'desc')
            ->get();

        return response()->json($equiposEnStock);
    }

    public function getCantidadEquiposEnStockJSON(Request $request)
    {
        $stock911 = Recurso::where('nombre', 'Stock 911')->first();

        if (!$stock911) {
            return response()->json(['error' => 'Recurso Stock 911 no encontrado'], 404);
        }

        $estados = $this->getEstadosIds();

        $cantidad = FlotaGeneral::where('recurso_id', $stock911->id)
            ->where('destino_id', $stock911->destino_id)
            ->whereNull('fecha_desasignacion')
            ->whereHas('equipo', fn($q) => $q->where('estado_id', '!=', $estados['No funciona']))
            ->count();

        return response()->json(['cantidad_equipos_en_stock' => $cantidad]);
    }

    public function getEquiposPorDepartamentalJSON(Request $request)
    {
        $query = $this->equiposPorDestinoQuery('Departamental Paraná (JDP)', 'departamental');

        if (!$query) {
            return response()->json(['error' => 'Departamental Paraná no encontrada'], 404);
        }

        return response()->json($query->get());
    }

    public function getCantidadEquiposEnDepartamentalJSON(Request $request)
    {
        $departamentalParana = Destino::where('nombre', 'Departamental Paraná (JDP)')->first();

        if (!$departamentalParana) {
            return response()->json(['error' => 'Departamental Paraná no encontrada'], 404);
        }

        $estados = $this->getEstadosIds();

        $cantidad = FlotaGeneral::whereHas(
            'destino',
            fn($q) =>
            $q->where('departamental_id', $departamentalParana->departamental_id)
        )
            ->whereExists(
                fn($q) =>
                $q->select(DB::raw(1))
                    ->from('historico')
                    ->whereRaw('flota_general.equipo_id = historico.equipo_id')
                    ->whereNull('historico.fecha_desasignacion')
            )
            ->whereHas('equipo', fn($q) => $q->where('estado_id', '!=', $estados['No funciona']))
            ->count();

        return response()->json(['cantidad_equipos_en_departamental' => $cantidad]);
    }

    public function getEquiposDivision911JSON(Request $request)
    {
        $query = $this->equiposPorDestinoQuery('División 911 y Videovigilancia', 'division');

        if (!$query) {
            return response()->json(['error' => 'División 911 no encontrada'], 404);
        }

        return response()->json($query->get());
    }

    public function getCantidadEquiposEnDivision911JSON(Request $request)
    {
        $division911 = Destino::where('nombre', 'División 911 y Videovigilancia')->first();

        if (!$division911) {
            return response()->json(['error' => 'División 911 no encontrada'], 404);
        }

        $estados = $this->getEstadosIds();

        $cantidad = FlotaGeneral::whereHas(
            'destino',
            fn($q) =>
            $q->where('division_id', $division911->division_id)
        )
            ->whereExists(
                fn($q) =>
                $q->select(DB::raw(1))
                    ->from('historico')
                    ->whereRaw('flota_general.equipo_id = historico.equipo_id')
                    ->whereNull('historico.fecha_desasignacion')
            )
            ->whereHas('equipo', fn($q) => $q->where('estado_id', '!=', $estados['No funciona']))
            ->count();

        return response()->json(['cantidad_equipos_en_division_911' => $cantidad]);
    }

    public function getEquiposDivisionBancariaJSON(Request $request)
    {
        $query = $this->equiposPorDestinoQuery('División Seguridad Urbana y Bancaria', 'division');

        if (!$query) {
            return response()->json(['error' => 'División Bancaria no encontrada'], 404);
        }

        return response()->json($query->get());
    }

    public function getCantidadEquiposEnDivisionBancariaJSON(Request $request)
    {
        $divisionBancaria = Destino::where('nombre', 'División Seguridad Urbana y Bancaria')->first();

        if (!$divisionBancaria) {
            return response()->json(['error' => 'División Bancaria no encontrada'], 404);
        }

        $estados = $this->getEstadosIds();

        $cantidad = FlotaGeneral::whereHas(
            'destino',
            fn($q) =>
            $q->where('division_id', $divisionBancaria->division_id)
        )
            ->whereExists(
                fn($q) =>
                $q->select(DB::raw(1))
                    ->from('historico')
                    ->whereRaw('flota_general.equipo_id = historico.equipo_id')
                    ->whereNull('historico.fecha_desasignacion')
            )
            ->whereHas('equipo', fn($q) => $q->where('estado_id', '!=', $estados['No funciona']))
            ->count();

        return response()->json(['cantidad_equipos_en_division_bancaria' => $cantidad]);
    }

    public function getCantidadDesinstalacionesParcialesJSON(Request $request)
    {
        $cantidad = Historico::whereIn('id', function ($query) {
            $query->select(DB::raw('MAX(id)'))
                ->from('historico')
                ->groupBy('equipo_id');
        })
            ->whereIn('tipo_movimiento_id', function ($subquery) {
                $subquery->select('id')
                    ->from('tipo_movimiento')
                    ->where('nombre', 'Desinstalación Parcial');
            })
            ->count();

        return response()->json(['cantidad_desinstalaciones_parciales' => $cantidad]);
    }

    //agrego metodos para equipos UOM 11/12/2025
    public function getUOMDisponiblesJSON(Request $request)
    {
        $equipos = FlotaGeneral::whereDoesntHave('entregasActivas')
            ->whereHas('equipo.tipo_terminal.tipo_uso', function ($query) {
                $query->where('uso', 'portatil');
            })
            ->whereHas('recurso', function ($query) {
                $query->where('nombre', 'Unidad Operativa Móvil');
            })
            ->with(['equipo'])
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->equipo->id ?? '',
                    'tei' => $item->equipo->tei ?? '',
                    'modelo' => $item->equipo->modelo ?? '',
                ];
            });

        return response()->json($equipos);
    }

    public function getUOMNoDisponiblesJSON(Request $request)
    {
        $equipos = FlotaGeneral::whereHas('entregasActivas')
            ->whereHas('equipo.tipo_terminal.tipo_uso', function ($query) {
                $query->where('uso', 'portatil');
            })
            ->whereHas('recurso', function ($query) {
                $query->where('nombre', 'Unidad Operativa Móvil');
            })
            ->with(['equipo'])
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->equipo->id ?? '',
                    'tei' => $item->equipo->tei ?? '',
                    'modelo' => $item->equipo->modelo ?? '',
                ];
            });

        return response()->json($equipos);
    }


}
