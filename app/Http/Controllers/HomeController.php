<?php

namespace App\Http\Controllers;

use App\Models\Sitio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\User;
use Spatie\Permission\Models\Role;
use App\Models\Camara;
use App\Models\Equipo;
use App\Models\Estado;
use App\Models\EventoCecoco;
use App\Models\FlotaGeneral;
use App\Models\Destino;
use App\Models\Recurso;
use App\Models\Historico;
use Illuminate\Support\Facades\DB;
use App\Models\EntregaEquipo;
use App\Models\EntregaBodycam;
use App\Models\TareaItem;
use Carbon\Carbon;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        // Obtener IDs de estados una sola vez
        $estados = Estado::whereIn('nombre', [
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

        // Contadores básicos
        $cant_usuarios = User::count();
        $cant_roles = Role::count();
        //Camaras activas que no sean BDE (Totem)
        $cant_camaras = Camara::whereHas('tipoCamara', function ($query) {
            $query->where('tipo', '!=', 'BDE (Totem)');
        })->whereHas('sitio', fn($q) => $q->where('activo', true))->count();
        //BDE (Totem)
        $cant_camaras_bde = Camara::whereHas('tipoCamara', function ($query) {
            $query->where('tipo', 'BDE (Totem)');
        })->count();
        //Sitios Activos
        $cant_sitios_activos = Sitio::where('activo', true)->count();
        //Sitios Inactivos
        $cant_sitios_inactivos = Sitio::where('activo', false)->count();

        // Equipos por estado
        $cant_equipos_sin_funcionar = Equipo::where('estado_id', $estados['No funciona'])->count();
        $cant_equipos_temporales = Equipo::where('estado_id', $estados['Temporal'])->count();
        $cant_equipos_en_revision = Equipo::where('estado_id', $estados['En revision'])->count();
        $cant_equipos_baja = Equipo::whereIn('estado_id', [
            $estados['Baja'],
            $estados['Recambio'],
            $estados['Perdido']
        ])->count();
        $cant_equipos_funcionales = Equipo::whereIn('estado_id', [
            $estados['Nuevo'],
            $estados['Usado'],
            $estados['Reparado']
        ])->count();

        // Equipos por proveedor
        $cant_equipos_provisto_por_pg = Equipo::where('provisto', 'Patagonia Green')->count();
        $cant_equipos_provisto_por_telecom = Equipo::where('provisto', 'Telecom')->count();
        $cant_equipos_provisto_por_per = Equipo::where('provisto', 'Policía de Entre Ríos')->count();

        // Equipos en Stock 911
        $stock911 = Recurso::where('nombre', 'Stock 911')->first();
        $cant_equipos_en_stock = $stock911
            ? FlotaGeneral::where('recurso_id', $stock911->id)
                ->where('destino_id', $stock911->destino_id)
                ->where('fecha_desasignacion', null)
                ->whereHas('equipo', fn($q) => $q->whereNotIn('estado_id', [
                    $estados['No funciona'],
                    $estados['Baja'],
                    $estados['Perdido']
                ]))
                ->count()
            : 0;

        // Equipos en Departamental Paraná
        $departamentalParana = Destino::where('nombre', 'Departamental Paraná (JDP)')->first();
        $cant_equipos_en_departamental = 0;
        if ($departamentalParana) {
            $destinosHijos = $departamentalParana->getDestinosHijosRecursivo();
            $cant_equipos_en_departamental = Historico::whereIn('destino_id', $destinosHijos)
                ->whereNull('fecha_desasignacion')
                ->whereHas('equipo', fn($q) => $q->whereNotIn('estado_id', [
                    $estados['No funciona'],
                    $estados['Baja'],
                    $estados['Perdido']
                ]))
                ->count();
        }

        // Equipos en División 911
        $division911 = Destino::where('nombre', 'División 911 y Videovigilancia')->first();
        $cant_equipos_en_div_911 = 0;
        if ($division911) {
            $destinosHijos = $division911->getDestinosHijosRecursivo();
            $cant_equipos_en_div_911 = Historico::whereIn('destino_id', $destinosHijos)
                ->whereNull('fecha_desasignacion')
                ->whereHas('equipo', fn($q) => $q->whereNotIn('estado_id', [
                    $estados['No funciona'],
                    $estados['Baja'],
                    $estados['Perdido']
                ]))
                ->count();
        }

        // Equipos en División Bancaria
        $divisionBancaria = Destino::where('nombre', 'División Seguridad Urbana y Bancaria')->first();
        $cant_equipos_en_div_bancaria = 0;
        if ($divisionBancaria) {
            $destinosHijos = $divisionBancaria->getDestinosHijosRecursivo();
            $cant_equipos_en_div_bancaria = Historico::whereIn('destino_id', $destinosHijos)
                ->whereNull('fecha_desasignacion')
                ->whereHas('equipo', fn($q) => $q->whereNotIn('estado_id', [
                    $estados['No funciona'],
                    $estados['Baja'],
                    $estados['Perdido']
                ]))
                ->count();
        }

        // Equipos en Patagonia Green
        $destPG = Destino::where('nombre', 'Patagonia Green')->first();
        $cant_equipos_en_pg = $destPG
            ? Historico::where('destino_id', $destPG->id)
                ->where('fecha_desasignacion', null)
                ->whereHas('equipo', fn($q) => $q->whereNotIn('estado_id', [
                    $estados['No funciona'],
                    $estados['Baja'],
                    $estados['Perdido']
                ]))
                ->count()
            : 0;

        // Desinstalaciones parciales
        $cant_desinstalaciones = Historico::whereIn('id', function ($query) {
            $query->select(DB::raw('MAX(id)'))
                ->from('historico')
                ->groupBy('equipo_id');
        })
            ->where('tipo_movimiento_id', function ($subquery) {
                $subquery->select('id')
                    ->from('tipo_movimiento')
                    ->where('nombre', 'Desinstalación Parcial');
            })
            ->count();

        // Datos para pestaña Novedades
        $fecha_actual = Carbon::now()->format('d/m/Y H:i');
        $hoy = Carbon::today();

        // Entregas activas de equipos (no devueltas completamente)
        $entregas_equipos_activas = EntregaEquipo::with(['equipos', 'devoluciones.equipos'])
            ->whereIn('estado', ['entregado', 'devolucion_parcial'])
            ->orderBy('fecha_entrega', 'desc')
            ->get();

        // Entregas activas de bodycams (no devueltas completamente)
        $entregas_bodycams_activas = EntregaBodycam::with(['bodycams', 'devoluciones.bodycams'])
            ->whereIn('estado', [EntregaBodycam::ESTADO_ENTREGADA, EntregaBodycam::ESTADO_PARCIALMENTE_DEVUELTA])
            ->orderBy('fecha_entrega', 'desc')
            ->get();

        // Contar equipos actualmente entregados (sin devolver)
        $cant_equipos_entregados_total = 0;
        foreach ($entregas_equipos_activas as $entrega) {
            $equiposDevueltos = $entrega->devoluciones()
                ->with('equipos')
                ->get()
                ->pluck('equipos')
                ->flatten()
                ->pluck('id')
                ->unique()
                ->count();
            $cant_equipos_entregados_total += ($entrega->equipos->count() - $equiposDevueltos);
        }

        // Contar bodycams actualmente entregadas (sin devolver)
        $cant_bodycams_entregadas_total = 0;
        foreach ($entregas_bodycams_activas as $entrega) {
            $bodycamsDevueltas = $entrega->devoluciones()
                ->with('bodycams')
                ->get()
                ->pluck('bodycams')
                ->flatten()
                ->pluck('id')
                ->unique()
                ->count();
            $cant_bodycams_entregadas_total += ($entrega->bodycams->count() - $bodycamsDevueltas);
        }

        $tareas_en_proceso = TareaItem::with(['tarea'])
            ->where('estado', TareaItem::ESTADO_EN_PROCESO)
            ->orderBy('fecha_programada', 'asc')
            ->get();

        $tareas_hoy = TareaItem::with(['tarea'])
            ->whereDate('fecha_programada', $hoy)
            ->where('estado', TareaItem::ESTADO_PENDIENTE)
            ->orderBy('fecha_programada', 'asc')
            ->get();

        $cant_tareas_hoy = $tareas_hoy->count() + $tareas_en_proceso->count();

        $manana = Carbon::tomorrow();
        $tareas_manana = TareaItem::with('tarea')
            ->whereDate('fecha_programada', $manana)
            ->where('estado', TareaItem::ESTADO_PENDIENTE)
            ->orderBy('fecha_programada')
            ->get();

        // Datos agrupados para gráficos
        // Cámaras por tipo (agrupadas por el campo 'tipo' exacto de tipo_camara)
        $camaras_por_tipo = Camara::whereHas('sitio', fn($q) => $q->where('activo', true))
            ->with('tipoCamara')
            ->get()
            ->groupBy(function($camara) {
                $tipo = $camara->tipoCamara->tipo ?? 'Sin tipo';
                return stripos($tipo, 'LPR') !== false ? 'LPR' : $tipo;
            })
            ->map(fn($group, $tipo) => [
                'tipo' => $tipo,
                'total' => $group->count(),
            ])
            ->values();

        // Equipos por departamental (top 10) - contando todas las dependencias hijas
        $departamentales = Destino::where('tipo', 'departamental')->get();
        $equipos_por_departamental = collect();
        
        foreach ($departamentales as $dept) {
            $destinosHijos = $dept->getDestinosHijosRecursivo();
            $total = Historico::whereIn('destino_id', $destinosHijos)
                ->whereNull('fecha_desasignacion')
                ->whereHas('equipo', fn($q) => $q->whereNotIn('estado_id', [
                    $estados['No funciona'],
                    $estados['Baja'],
                    $estados['Perdido']
                ]))
                ->count();
            
            if ($total > 0) {
                $equipos_por_departamental->push((object)[
                    'nombre' => $dept->nombre,
                    'total' => $total
                ]);
            }
        }
        
        $equipos_por_departamental = $equipos_por_departamental
            ->sortByDesc('total')
            ->take(10)
            ->values();

        // Equipos por división (top 10) - contando todas las dependencias hijas
        $divisiones = Destino::where('tipo', 'division')->with('padre')->get();
        $equipos_por_division = collect();
        
        foreach ($divisiones as $div) {
            $destinosHijos = $div->getDestinosHijosRecursivo();
            $total = Historico::whereIn('destino_id', $destinosHijos)
                ->whereNull('fecha_desasignacion')
                ->whereHas('equipo', fn($q) => $q->whereNotIn('estado_id', [
                    $estados['No funciona'],
                    $estados['Baja'],
                    $estados['Perdido']
                ]))
                ->count();
            
            if ($total > 0) {
                $equipos_por_division->push((object)[
                    'nombre' => $div->nombre,
                    'dependencia' => $div->padre->nombre ?? null,
                    'total' => $total
                ]);
            }
        }
        
        $equipos_por_division = $equipos_por_division
            ->sortByDesc('total')
            ->take(10)
            ->values();

        // ── Cecoco: estadísticas de la semana anterior (lunes→domingo) ──────────
        $cecocoInicioSemana = Carbon::now()->startOfWeek(Carbon::MONDAY)->subWeek()->startOfDay();
        $cecocoFinSemana    = $cecocoInicioSemana->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay();

        // Tipificaciones a excluir del dashboard
        $cecocoExcluidos = "no responde|llamada falsa|llamadas falsas|no atiende|llamada erronea|llamada err";

        $cecocoBase = EventoCecoco::whereBetween('fecha_hora', [$cecocoInicioSemana, $cecocoFinSemana])
            ->whereRaw("LOWER(COALESCE(tipo_servicio, '')) NOT REGEXP ?", [$cecocoExcluidos]);

        $cecoco_total_semana   = (clone $cecocoBase)->count();

        $cecoco_accidentes     = (clone $cecocoBase)
            ->whereRaw("LOWER(tipo_servicio) REGEXP 'accidente.*(lesion|herido|lesionad)'")
            ->count();

        $cecoco_robos          = (clone $cecocoBase)
            ->whereRaw("LOWER(tipo_servicio) LIKE '%robo%'")
            ->count();

        $cecoco_hurtos         = (clone $cecocoBase)
            ->whereRaw("LOWER(tipo_servicio) LIKE '%hurto%'")
            ->count();

        $cecoco_abuso_armas    = (clone $cecocoBase)
            ->whereRaw("LOWER(tipo_servicio) REGEXP 'abuso.*(arma|fuego)|arma de fuego'")
            ->count();

        $cecoco_homicidios     = (clone $cecocoBase)
            ->whereRaw("LOWER(tipo_servicio) LIKE '%homicidio%'")
            ->count();

        $cecoco_hechos         = $cecoco_accidentes + $cecoco_robos + $cecoco_hurtos
                                 + $cecoco_abuso_armas + $cecoco_homicidios;

        $cecoco_periodo_label  = $cecocoInicioSemana->locale('es')->isoFormat('D [de] MMMM')
                                 . ' al '
                                 . $cecocoFinSemana->locale('es')->isoFormat('D [de] MMMM [de] YYYY');

        return view('home', compact(
            'cant_usuarios',
            'cant_roles',
            'cant_equipos_en_stock',
            'cant_equipos_en_departamental',
            'cant_equipos_en_pg',
            'cant_equipos_provisto_por_pg',
            'cant_equipos_provisto_por_telecom',
            'cant_equipos_provisto_por_per',
            'cant_camaras',
            'cant_camaras_bde',
            'cant_sitios_activos',
            'cant_sitios_inactivos',
            'cant_desinstalaciones',
            'cant_equipos_en_div_911',
            'cant_equipos_sin_funcionar',
            'cant_equipos_funcionales',
            'cant_equipos_temporales',
            'cant_equipos_baja',
            'cant_equipos_en_revision',
            'cant_equipos_en_div_bancaria',
            'fecha_actual',
            'cant_equipos_entregados_total',
            'cant_bodycams_entregadas_total',
            'cant_tareas_hoy',
            'tareas_en_proceso',
            'tareas_hoy',
            'tareas_manana',
            'entregas_equipos_activas',
            'entregas_bodycams_activas',
            'camaras_por_tipo',
            'equipos_por_departamental',
            'equipos_por_division',
            'cecoco_total_semana',
            'cecoco_hechos',
            'cecoco_accidentes',
            'cecoco_robos',
            'cecoco_hurtos',
            'cecoco_abuso_armas',
            'cecoco_homicidios',
            'cecoco_periodo_label'
        ));
    }

    /**
     * Datos para el mini mapa de calor del tab Cecoco en el dashboard.
     * Devuelve puntos geocodificados de la semana anterior (sin llamadas falsas / no responde).
     */
    public function cecocoMapaDatos(Request $request): JsonResponse
    {
        $inicio = Carbon::now()->startOfWeek(Carbon::MONDAY)->subWeek()->startOfDay();
        $fin    = $inicio->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay();

        $excluidos = "no responde|llamada falsa|llamadas falsas|no atiende|llamada erronea|llamada err";
        $tipo      = $request->input('tipo');

        $query = DB::table('evento_cecoco as e')
            ->join('geocodificacion_directa as g', function ($join) {
                $join->on(
                    DB::raw("UPPER(TRIM(SUBSTRING_INDEX(e.direccion, ' AL ', 1)))"),
                    '=',
                    'g.direccion_original'
                );
            })
            ->whereBetween('e.fecha_hora', [$inicio, $fin])
            ->whereRaw("LOWER(COALESCE(e.tipo_servicio, '')) NOT REGEXP ?", [$excluidos])
            ->whereNotNull('g.latitud')
            ->whereNotNull('g.longitud');

        if ($tipo) {
            if ($tipo === 'Dispositivo Dual') {
                $query->whereRaw("e.tipo_servicio REGEXP '^[Dd]\\.?[[:space:]]*[Dd]\\.?$'");
            } else {
                $query->whereRaw("LOWER(e.tipo_servicio) LIKE ?", ['%' . strtolower($tipo) . '%']);
            }
        }

        $puntos = $query
            ->select(DB::raw('g.latitud, g.longitud, COUNT(*) as peso'))
            ->groupBy('g.latitud', 'g.longitud')
            ->get();

        return response()->json($puntos);
    }
}
