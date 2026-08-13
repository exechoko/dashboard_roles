<?php

namespace App\Http\Controllers;

use App\Exports\EquipamientoEstadisticasExport;
use App\Models\FlotaGeneral;
use Illuminate\Support\Facades\DB;
use App\Models\Recurso;
use App\Models\Destino;
use App\Models\Equipo;
use App\Models\Estado;
use App\Models\Historico;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;

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
                'En revision',
                'Degradado - Sin Accesorios',
            ])->pluck('id', 'nombre');
        }
        return $this->estadosCache;
    }

    /**
     * Página de estadísticas de equipamiento (menú Equipamientos): cantidades por
     * situación de instalación (móvil/base/portátil/stock/desinstalado), por
     * condición (operativo/no operativo/en revisión técnica), por dependencia y
     * por tipo de equipo.
     */
    public function equipamientoEstadisticas(Request $request)
    {
        $datos = $this->calcularEquipamientoEstadisticas();

        return view('equipos.estadisticas', $datos);
    }

    /**
     * Descarga en Excel (una hoja por bloque) de las estadísticas de equipamiento.
     */
    public function exportEquipamientoEstadisticasExcel()
    {
        $datos = $this->calcularEquipamientoEstadisticas();

        return Excel::download(
            new EquipamientoEstadisticasExport($datos),
            'EstadisticasEquipamiento_' . Carbon::now()->format('Y-m-d_His') . '.xlsx'
        );
    }

    /**
     * Detalle de equipos (JSON) para los modales de la página de estadísticas de
     * equipamiento: recibe filtros opcionales por querystring y devuelve la lista de
     * equipos que cumple esa combinación, para poder hacer clic en cualquier
     * contador/fila de las tablas y ver el detalle en una tabla con buscador.
     */
    public function equipamientoEstadisticasDetalleJSON(Request $request)
    {
        $stock911 = Recurso::where('nombre', 'Stock 911')->first();

        $operativoIds = Equipo::operativoEstadoIds();
        $noOperativoIds = Equipo::noOperativoEstadoIds();

        $query = Equipo::query()
            ->select(
                'equipos.id',
                'equipos.tei',
                'equipos.issi',
                DB::raw("DATE_FORMAT(equipos.fecha_estado, '%d/%m/%Y %H:%i') as fecha_estado_fmt"),
                'tipo_terminales.marca as marca',
                'tipo_terminales.modelo as modelo',
                'tipo_uso.uso as uso',
                'estados.nombre as estado',
                'recursos.nombre as recurso',
                'destino.nombre as dependencia'
            )
            ->join('tipo_terminales', 'equipos.tipo_terminal_id', '=', 'tipo_terminales.id')
            ->join('tipo_uso', 'tipo_terminales.tipo_uso_id', '=', 'tipo_uso.id')
            ->join('estados', 'equipos.estado_id', '=', 'estados.id')
            ->leftJoin('flota_general', 'flota_general.equipo_id', '=', 'equipos.id')
            ->leftJoin('recursos', 'flota_general.recurso_id', '=', 'recursos.id')
            ->leftJoin('destino', 'flota_general.destino_id', '=', 'destino.id');

        if ($request->filled('estado_in')) {
            $nombres = array_filter(explode(',', $request->string('estado_in')));
            $query->whereIn('estados.nombre', $nombres);
        } elseif ($request->filled('condicion')) {
            match ($request->string('condicion')->toString()) {
                'operativo' => $query->whereIn('equipos.estado_id', $operativoIds),
                'no_operativo' => $query->whereIn('equipos.estado_id', $noOperativoIds),
                'otros' => $query->whereNotIn('equipos.estado_id', $operativoIds->merge($noOperativoIds)),
                default => null,
            };
        }

        if ($request->filled('marca')) {
            $query->where('tipo_terminales.marca', $request->string('marca'));
        }

        if ($request->filled('modelo')) {
            $query->where('tipo_terminales.modelo', $request->string('modelo'));
        }

        if ($request->filled('excluir_marcas')) {
            $marcas = array_filter(explode(',', $request->string('excluir_marcas')));
            $query->whereNotIn('tipo_terminales.marca', $marcas);
        }

        // Pares "Marca:Modelo" a excluir (ej. para separar HTT500 de "Portátil")
        if ($request->filled('excluir_modelos')) {
            $pares = array_filter(explode(',', $request->string('excluir_modelos')));
            foreach ($pares as $par) {
                [$m, $mo] = array_pad(explode(':', $par, 2), 2, null);
                $query->where(fn ($q) => $q->where('tipo_terminales.marca', '!=', $m)->orWhere('tipo_terminales.modelo', '!=', $mo));
            }
        }

        // Pares "Marca:Modelo" a incluir, unidos por OR (ej. MDT400/DT410/SRG3900)
        if ($request->filled('modelos')) {
            $pares = array_filter(explode(',', $request->string('modelos')));
            $query->where(function ($q) use ($pares) {
                foreach ($pares as $par) {
                    [$m, $mo] = array_pad(explode(':', $par, 2), 2, null);
                    $q->orWhere(fn ($q2) => $q2->where('tipo_terminales.marca', $m)->where('tipo_terminales.modelo', $mo));
                }
            });
        }

        if ($request->filled('uso')) {
            $query->where('tipo_uso.uso', $request->string('uso'));
        }

        if ($request->boolean('excluir_portatil')) {
            $query->where('tipo_uso.uso', '!=', 'Portatil');
        }

        if ($request->filled('destino_id')) {
            $query->where('flota_general.destino_id', $request->integer('destino_id'));
        }

        if ($request->filled('situacion')) {
            match ($request->string('situacion')->toString()) {
                'instalado' => $query->when(
                    $stock911,
                    fn ($q) => $q->where(fn ($q2) => $q2->whereNull('flota_general.recurso_id')->orWhere('flota_general.recurso_id', '!=', $stock911->id))
                ),
                'en_stock' => $query->when(
                    $stock911,
                    fn ($q) => $q->where('flota_general.recurso_id', $stock911->id),
                    fn ($q) => $q->whereRaw('1 = 0')
                ),
                default => null,
            };
        }

        if ($request->boolean('sin_movimiento_3y')) {
            $ultimosHistoricoIds = DB::table('historico')->selectRaw('MAX(id) as id')->groupBy('equipo_id');
            $equiposConMovimientoRecienteIds = Historico::whereIn('id', $ultimosHistoricoIds)
                ->where('fecha_asignacion', '>=', now()->subYears(3))
                ->pluck('equipo_id');
            $query->whereNotIn('equipos.id', $equiposConMovimientoRecienteIds);
        }

        // Equipos cuyo último movimiento histórico es de un tipo dado (ej. "Revisión")
        if ($request->filled('ultimo_movimiento')) {
            $ultimosHistoricoIds = DB::table('historico')->selectRaw('MAX(id) as id')->groupBy('equipo_id');
            $equiposIds = Historico::whereIn('id', $ultimosHistoricoIds)
                ->whereIn('tipo_movimiento_id', function ($q) use ($request) {
                    $q->select('id')->from('tipo_movimiento')->where('nombre', $request->string('ultimo_movimiento'));
                })
                ->pluck('equipo_id');
            $query->whereIn('equipos.id', $equiposIds);
        }

        $equipos = $query
            ->orderBy('tipo_terminales.marca')
            ->orderBy('tipo_terminales.modelo')
            ->orderBy('equipos.tei')
            ->limit(1000)
            ->get();

        return response()->json($equipos);
    }

    /**
     * Calcula los datos de la página/export de estadísticas de equipamiento.
     *
     * @return array{resumen: array, porTipoUso: \Illuminate\Support\Collection, porDependencia: \Illuminate\Support\Collection, porTipoEquipo: \Illuminate\Support\Collection, porEstado: \Illuminate\Support\Collection}
     */
    private function calcularEquipamientoEstadisticas(): array
    {
        $operativoIds = Equipo::operativoEstadoIds();
        $noOperativoIds = Equipo::noOperativoEstadoIds();

        $operativoIdsSql = $operativoIds->isNotEmpty() ? $operativoIds->implode(',') : '0';
        $noOperativoIdsSql = $noOperativoIds->isNotEmpty() ? $noOperativoIds->implode(',') : '0';

        $totalEquipos = Equipo::count();
        $totalOperativos = Equipo::whereIn('estado_id', $operativoIds)->count();
        $totalNoOperativos = Equipo::whereIn('estado_id', $noOperativoIds)->count();
        $totalOtrosEstados = $totalEquipos - $totalOperativos - $totalNoOperativos;

        // "En revisión técnica" según el último movimiento histórico de cada equipo
        // (no según el estado, tal como se releva la revisión de soporte/sección técnica).
        $ultimosHistoricoIds = DB::table('historico')
            ->selectRaw('MAX(id) as id')
            ->groupBy('equipo_id');

        $totalEnRevisionTecnica = Historico::whereIn('id', $ultimosHistoricoIds)
            ->whereIn('tipo_movimiento_id', function ($q) {
                $q->select('id')->from('tipo_movimiento')->where('nombre', 'Revisión');
            })
            ->count();

        // Situación de instalación: en flota_general cada equipo tiene siempre una única
        // fila con su ubicación actual (no se usa fecha_desasignacion). Solo lo que tiene
        // uso Móvil/Base/Base-Móvil está realmente "instalado" en un vehículo o base fija;
        // los portátiles asignados a una persona no están "instalados", están "asignados".
        $stock911 = Recurso::where('nombre', 'Stock 911')->first();

        // Solo cuentan como "instalado"/"asignado" (es decir, en servicio de verdad) los
        // equipos operativos (Nuevo/Usado/Reparado). Un equipo "Degradado - Sin Accesorios"
        // (u otro no operativo) puede seguir figurando con un recurso/destino asignado en
        // flota_general porque todavía no se retiró del lugar, pero no está prestando
        // servicio, así que no debe sumar acá.
        $asignadosActivosQuery = fn () => FlotaGeneral::query()
            ->join('equipos', 'flota_general.equipo_id', '=', 'equipos.id')
            ->join('tipo_terminales', 'equipos.tipo_terminal_id', '=', 'tipo_terminales.id')
            ->join('tipo_uso', 'tipo_terminales.tipo_uso_id', '=', 'tipo_uso.id')
            ->whereIn('equipos.estado_id', $operativoIds)
            ->when($stock911, fn ($q) => $q->where('flota_general.recurso_id', '!=', $stock911->id));

        $totalInstalados = $asignadosActivosQuery()
            ->where('tipo_uso.uso', '!=', 'Portatil')
            ->distinct('flota_general.equipo_id')
            ->count('flota_general.equipo_id');

        // El HTT500 se separa del resto de los portátiles: ya no quedan baterías ni antenas
        // para equiparlos, así que aunque el estado diga "Usado"/"Nuevo"/"Reparado" no se
        // cuentan como portátil operativo disponible, se muestran aparte. El VX-261
        // (Motorola/Vertex) tampoco cuenta con los portátiles TETRA porque no es TETRA
        // (es analógico/DMR, otra red), así que también se separa.
        $esHtt500 = fn ($q) => $q->where('tipo_terminales.marca', 'Teltronic')->where('tipo_terminales.modelo', 'HTT500');
        $esVertex = fn ($q) => $q->where('tipo_terminales.marca', 'Motorola/Vertex');

        $totalAsignadosPortatiles = $asignadosActivosQuery()
            ->where('tipo_uso.uso', 'Portatil')
            ->where(fn ($q) => $q->where('tipo_terminales.marca', '!=', 'Teltronic')->orWhere('tipo_terminales.modelo', '!=', 'HTT500'))
            ->where('tipo_terminales.marca', '!=', 'Motorola/Vertex')
            ->distinct('flota_general.equipo_id')
            ->count('flota_general.equipo_id');

        $totalHtt500Asignados = $asignadosActivosQuery()
            ->where('tipo_uso.uso', 'Portatil')
            ->where($esHtt500)
            ->distinct('flota_general.equipo_id')
            ->count('flota_general.equipo_id');

        $totalVertexAsignados = $asignadosActivosQuery()
            ->where('tipo_uso.uso', 'Portatil')
            ->where($esVertex)
            ->distinct('flota_general.equipo_id')
            ->count('flota_general.equipo_id');

        // No operativos (rotos/degradados/perdidos/baja) que todavía figuran asignados a un
        // recurso real (no en Stock 911): equipos que habría que retirar de la calle.
        $totalNoOperativosEnTerreno = FlotaGeneral::query()
            ->join('equipos', 'flota_general.equipo_id', '=', 'equipos.id')
            ->whereIn('equipos.estado_id', $noOperativoIds)
            ->when($stock911, fn ($q) => $q->where('flota_general.recurso_id', '!=', $stock911->id))
            ->distinct('flota_general.equipo_id')
            ->count('flota_general.equipo_id');

        // "Desinstalado" solo aplica a los equipos que realmente se instalan de forma
        // fija en un móvil/base (MDT400, DT410, SRG3900): a un portátil no se lo
        // "desinstala", simplemente no está asignado. El resto de lo que está en
        // Stock 911 (portátiles, bases nuevas, etc.) se cuenta aparte como "en depósito".
        $tipoTerminalesInstalables = DB::table('tipo_terminales')
            ->where(function ($q) {
                $q->where(function ($q2) {
                    $q2->where('marca', 'Teltronic')->whereIn('modelo', ['MDT400', 'DT410']);
                })->orWhere(function ($q2) {
                    $q2->where('marca', 'Sepura')->where('modelo', 'SRG3900');
                });
            })
            ->pluck('id');

        $totalDesinstalados = $stock911
            ? FlotaGeneral::where('recurso_id', $stock911->id)
                ->whereHas('equipo', fn ($q) => $q->whereIn('tipo_terminal_id', $tipoTerminalesInstalables))
                ->distinct('equipo_id')
                ->count('equipo_id')
            : 0;

        $totalEnDepositoOtros = $stock911
            ? FlotaGeneral::where('recurso_id', $stock911->id)
                ->whereHas('equipo', fn ($q) => $q->whereNotIn('tipo_terminal_id', $tipoTerminalesInstalables))
                ->distinct('equipo_id')
                ->count('equipo_id')
            : 0;

        // Reconciliación completa por tipo de uso: cada equipo tiene siempre una fila en
        // flota_general (instalados + no_operativo_terreno + otros_terreno + en_stock debe
        // dar siempre el total de esa fila, sin equipos "perdidos" entre categorías).
        // El HTT500 se desagrega del resto de los "Portátil" (ver nota más abajo).
        $stock911IdSql = $stock911->id ?? 0;
        $categoriaUsoSql = "CASE
            WHEN tipo_terminales.marca = 'Teltronic' AND tipo_terminales.modelo = 'HTT500' THEN 'HTT500 (sin accesorios)'
            WHEN tipo_terminales.marca = 'Motorola/Vertex' THEN 'VX-261 (no TETRA)'
            ELSE tipo_uso.uso
        END";

        $situacionPorTipoUso = FlotaGeneral::query()
            ->select(
                DB::raw("{$categoriaUsoSql} as uso"),
                DB::raw("SUM(CASE WHEN equipos.estado_id IN ({$operativoIdsSql}) AND flota_general.recurso_id != {$stock911IdSql} THEN 1 ELSE 0 END) as instalados"),
                DB::raw("SUM(CASE WHEN equipos.estado_id IN ({$noOperativoIdsSql}) AND flota_general.recurso_id != {$stock911IdSql} THEN 1 ELSE 0 END) as no_operativo_terreno"),
                DB::raw("SUM(CASE WHEN equipos.estado_id NOT IN ({$operativoIdsSql}) AND equipos.estado_id NOT IN ({$noOperativoIdsSql}) AND flota_general.recurso_id != {$stock911IdSql} THEN 1 ELSE 0 END) as otros_terreno"),
                DB::raw("SUM(CASE WHEN flota_general.recurso_id = {$stock911IdSql} THEN 1 ELSE 0 END) as en_stock"),
                DB::raw('COUNT(*) as total')
            )
            ->join('equipos', 'flota_general.equipo_id', '=', 'equipos.id')
            ->join('tipo_terminales', 'equipos.tipo_terminal_id', '=', 'tipo_terminales.id')
            ->join('tipo_uso', 'tipo_terminales.tipo_uso_id', '=', 'tipo_uso.id')
            ->groupBy(DB::raw($categoriaUsoSql))
            ->orderBy(DB::raw($categoriaUsoSql))
            ->get();

        // Instalados/asignados actualmente (operativos), agrupados por tipo de uso
        // (Portátil / Móvil / Base / Base - Móvil). El HTT500 se desagrega del resto de
        // los portátiles porque ya no hay baterías ni antenas para equiparlos.
        $porTipoUso = FlotaGeneral::query()
            ->select(DB::raw("{$categoriaUsoSql} as uso"), DB::raw('COUNT(DISTINCT flota_general.equipo_id) as cantidad'))
            ->join('equipos', 'flota_general.equipo_id', '=', 'equipos.id')
            ->join('tipo_terminales', 'equipos.tipo_terminal_id', '=', 'tipo_terminales.id')
            ->join('tipo_uso', 'tipo_terminales.tipo_uso_id', '=', 'tipo_uso.id')
            ->whereIn('equipos.estado_id', $operativoIds)
            ->whereNull('flota_general.fecha_desasignacion')
            ->when($stock911, fn ($q) => $q->where('flota_general.recurso_id', '!=', $stock911->id))
            ->groupBy(DB::raw($categoriaUsoSql))
            ->orderBy(DB::raw($categoriaUsoSql))
            ->get();

        // Detalle de lo instalado en móviles/bases (operativo), desagregado por marca y modelo
        $instaladosMovilBasePorMarca = FlotaGeneral::query()
            ->select(
                'tipo_uso.uso as uso',
                'tipo_terminales.marca as marca',
                'tipo_terminales.modelo as modelo',
                DB::raw('COUNT(DISTINCT flota_general.equipo_id) as cantidad')
            )
            ->join('equipos', 'flota_general.equipo_id', '=', 'equipos.id')
            ->join('tipo_terminales', 'equipos.tipo_terminal_id', '=', 'tipo_terminales.id')
            ->join('tipo_uso', 'tipo_terminales.tipo_uso_id', '=', 'tipo_uso.id')
            ->whereIn('equipos.estado_id', $operativoIds)
            ->where('tipo_uso.uso', '!=', 'Portatil')
            ->when($stock911, fn ($q) => $q->where('flota_general.recurso_id', '!=', $stock911->id))
            ->groupBy('tipo_uso.uso', 'tipo_terminales.id', 'tipo_terminales.marca', 'tipo_terminales.modelo')
            ->orderBy('tipo_uso.uso')
            ->orderBy('tipo_terminales.marca')
            ->orderBy('tipo_terminales.modelo')
            ->get();

        // Cantidades por dependencia (destino) de los equipos actualmente instalados
        $porDependencia = FlotaGeneral::query()
            ->select(
                'destino.id as destino_id',
                'destino.nombre as destino_nombre',
                'destino.tipo as destino_tipo',
                DB::raw('COUNT(DISTINCT flota_general.equipo_id) as total'),
                DB::raw("SUM(CASE WHEN equipos.estado_id IN ({$operativoIdsSql}) THEN 1 ELSE 0 END) as operativos"),
                DB::raw("SUM(CASE WHEN equipos.estado_id IN ({$noOperativoIdsSql}) THEN 1 ELSE 0 END) as no_operativos")
            )
            ->join('equipos', 'flota_general.equipo_id', '=', 'equipos.id')
            ->join('destino', 'flota_general.destino_id', '=', 'destino.id')
            ->whereNull('flota_general.fecha_desasignacion')
            ->groupBy('destino.id', 'destino.nombre', 'destino.tipo')
            ->orderByDesc('total')
            ->get();

        // Cantidades por tipo de equipo (marca / modelo)
        $porTipoEquipo = Equipo::query()
            ->select(
                'tipo_terminales.id as tipo_terminal_id',
                'tipo_terminales.marca as marca',
                'tipo_terminales.modelo as modelo',
                'tipo_uso.uso as uso',
                DB::raw('COUNT(equipos.id) as total'),
                DB::raw("SUM(CASE WHEN equipos.estado_id IN ({$operativoIdsSql}) THEN 1 ELSE 0 END) as operativos"),
                DB::raw("SUM(CASE WHEN equipos.estado_id IN ({$noOperativoIdsSql}) THEN 1 ELSE 0 END) as no_operativos")
            )
            ->join('tipo_terminales', 'equipos.tipo_terminal_id', '=', 'tipo_terminales.id')
            ->leftJoin('tipo_uso', 'tipo_terminales.tipo_uso_id', '=', 'tipo_uso.id')
            ->groupBy('tipo_terminales.id', 'tipo_terminales.marca', 'tipo_terminales.modelo', 'tipo_uso.uso')
            ->orderBy('tipo_terminales.marca')
            ->orderBy('tipo_terminales.modelo')
            ->get();

        $porEstado = Equipo::select('estados.nombre as estado', DB::raw('COUNT(equipos.id) as cantidad'))
            ->join('estados', 'equipos.estado_id', '=', 'estados.id')
            ->groupBy('estados.id', 'estados.nombre')
            ->orderByDesc('cantidad')
            ->get();

        // Equipos Teltronic HTT500 sin ningún movimiento histórico en los últimos 3 años
        // (o sin histórico), para detectar equipos "olvidados" cuyo estado nunca se
        // reconfirmó (el relevamiento Erbeta 2023 encontró varios así, degradados).
        $ultimosHistoricoIdsHtt = DB::table('historico')
            ->selectRaw('MAX(id) as id')
            ->groupBy('equipo_id');

        $htt500SinMovimiento = Equipo::query()
            ->select(
                'equipos.id',
                'equipos.tei',
                'equipos.issi',
                DB::raw("DATE_FORMAT(equipos.fecha_estado, '%d/%m/%Y %H:%i') as fecha_estado_fmt"),
                'estados.nombre as estado',
                'recursos.nombre as recurso',
                'destino.nombre as dependencia',
                'ultimo.fecha_asignacion as ultimo_movimiento_fecha',
                'ultimo_tipo.nombre as ultimo_movimiento_tipo'
            )
            ->join('tipo_terminales', 'equipos.tipo_terminal_id', '=', 'tipo_terminales.id')
            ->join('estados', 'equipos.estado_id', '=', 'estados.id')
            ->leftJoin('flota_general', 'flota_general.equipo_id', '=', 'equipos.id')
            ->leftJoin('recursos', 'flota_general.recurso_id', '=', 'recursos.id')
            ->leftJoin('destino', 'flota_general.destino_id', '=', 'destino.id')
            ->leftJoin('historico as ultimo', function ($join) use ($ultimosHistoricoIdsHtt) {
                $join->on('ultimo.equipo_id', '=', 'equipos.id')
                    ->whereIn('ultimo.id', $ultimosHistoricoIdsHtt);
            })
            ->leftJoin('tipo_movimiento as ultimo_tipo', 'ultimo.tipo_movimiento_id', '=', 'ultimo_tipo.id')
            ->where('tipo_terminales.marca', 'Teltronic')
            ->where('tipo_terminales.modelo', 'HTT500')
            // Solo estados "vivos" (se excluyen Baja/Perdido/Recambio, que ya son
            // bajas resueltas y su antigüedad de movimiento no es un problema a revisar).
            ->whereIn('estados.nombre', ['Usado', 'Nuevo', 'Reparado', 'Degradado - Sin Accesorios'])
            ->where(function ($q) {
                $q->whereNull('ultimo.fecha_asignacion')
                    ->orWhere('ultimo.fecha_asignacion', '<', now()->subYears(3));
            })
            ->orderBy('ultimo.fecha_asignacion')
            ->get();

        $resumen = [
            'total' => $totalEquipos,
            'operativos' => $totalOperativos,
            'no_operativos' => $totalNoOperativos,
            'otros_estados' => $totalOtrosEstados,
            'en_revision_tecnica' => $totalEnRevisionTecnica,
            'instalados' => $totalInstalados,
            'asignados_portatiles' => $totalAsignadosPortatiles,
            'htt500_asignados' => $totalHtt500Asignados,
            'vertex_asignados' => $totalVertexAsignados,
            'desinstalados' => $totalDesinstalados,
            'en_deposito_otros' => $totalEnDepositoOtros,
            'no_operativos_en_terreno' => $totalNoOperativosEnTerreno,
            'pct_operativo' => $totalEquipos > 0 ? round($totalOperativos / $totalEquipos * 100, 1) : 0,
            'pct_no_operativo' => $totalEquipos > 0 ? round($totalNoOperativos / $totalEquipos * 100, 1) : 0,
            'pct_otros' => $totalEquipos > 0 ? round($totalOtrosEstados / $totalEquipos * 100, 1) : 0,
            'htt500_sin_movimiento' => $htt500SinMovimiento->count(),
        ];

        return compact('resumen', 'porTipoUso', 'situacionPorTipoUso', 'instaladosMovilBasePorMarca', 'porDependencia', 'porTipoEquipo', 'porEstado', 'htt500SinMovimiento');
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
}
