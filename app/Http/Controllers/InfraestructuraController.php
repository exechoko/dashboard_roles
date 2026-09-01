<?php

namespace App\Http\Controllers;

use App\Jobs\ConsultarTamanoRestauracionesCecoco;
use App\Models\DispositivoEdificio;
use App\Models\InventarioConflicto;
use App\Models\InventarioDiscrepancia;
use App\Services\CecocoExpedienteService;
use App\Services\CentralTelefonicaTroncalesService;
use App\Services\GeocodificacionService;
use App\Services\LibreNmsService;
use App\Services\SnmpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InfraestructuraController extends Controller
{
    /**
     * Mapea cada grupo de pantalla a los `tipo` de dispositivos_edificio que
     * agrupa. Los puesto_video y puesto_cecoco quedan fuera: los primeros ya
     * se ven en la sección LibreNMS, los segundos están en otra subred.
     *
     * @var array<string, array<int, string>>
     */
    private const GRUPOS = [
        'pcs' => ['pc'],
        'servidores' => ['servidor', 'servidor_cecoco', 'servidor_nebula'],
        'camaras' => ['camara_interna'],
        'red' => ['router', 'switch'],
    ];

    public function __construct()
    {
        $this->middleware('permission:ver-infraestructura-pcs')->only(['pcs']);
        $this->middleware('permission:ver-infraestructura-servidores')->only(['servidores', 'estadoNominatim']);
        $this->middleware('permission:ver-infraestructura-camaras')->only(['camaras']);
        $this->middleware('permission:ver-infraestructura-red')->only(['red']);
        $this->middleware('permission:ver-infraestructura-librenms')->only(['librenms', 'estadoCctv']);
        $this->middleware('permission:ver-infraestructura-central-telefonica')->only(['centralTelefonica', 'estadoTroncalesCentralTelefonica']);
        $this->middleware('permission:ver-infraestructura-workers')->only([
            'workers', 'workersStatus', 'refreshRestauracionesCache', 'refreshRestauracionesGpsCache',
        ]);
        $this->middleware(['permission:ver-infraestructura-pcs|ver-infraestructura-servidores|ver-infraestructura-camaras|ver-infraestructura-red'])
            ->only(['estadoGrupo']);
        $this->middleware('permission:refrescar-infraestructura')->only(['refrescarDispositivo', 'toggleMonitoreo']);
    }

    public function pcs()
    {
        return view('infraestructura.pcs');
    }

    public function servidores()
    {
        return view('infraestructura.servidores');
    }

    /**
     * Estado del servidor de geocodificación (Nominatim), para la card de
     * servicios de la pantalla Servidores. Endpoint propio (en vez de reusar
     * workersStatus()) para no acoplar este permiso al de Workers y BD.
     */
    public function estadoNominatim(): JsonResponse
    {
        $online = Cache::remember(
            'dashboard_geo_servicio_online',
            55,
            fn () => app(GeocodificacionService::class)->nominatimDisponible()
        );

        return response()->json(['online' => $online]);
    }

    public function camaras()
    {
        return view('infraestructura.camaras');
    }

    public function red()
    {
        return view('infraestructura.red');
    }

    public function librenms()
    {
        return view('infraestructura.librenms');
    }

    public function centralTelefonica()
    {
        return view('infraestructura.central-telefonica');
    }

    public function workers()
    {
        return view('infraestructura.workers');
    }

    /**
     * Estado de un grupo de dispositivos (pcs/servidores/camaras/red): combina
     * el inventario de dispositivos_edificio con la última lectura de ping+SNMP
     * cacheada por el comando infraestructura:monitorear.
     */
    public function estadoGrupo(string $grupo): JsonResponse
    {
        if (!isset(self::GRUPOS[$grupo])) {
            abort(404);
        }

        $cache = Cache::get(SnmpService::CACHE_KEY_ESTADO, []);
        $lecturas = collect($cache['dispositivos'] ?? [])->keyBy('id');

        $dispositivos = DispositivoEdificio::activos()
            ->whereIn('tipo', self::GRUPOS[$grupo])
            ->orderBy('nombre')
            ->get()
            ->map(function (DispositivoEdificio $dispositivo) use ($lecturas): array {
                $lectura = $lecturas->get($dispositivo->id);
                $estado = !$dispositivo->monitoreo_habilitado
                    ? 'deshabilitado'
                    : ($lectura['estado'] ?? SnmpService::estadoSinLectura($dispositivo->ip));

                return [
                    'id' => $dispositivo->id,
                    'nombre' => $dispositivo->nombre,
                    'ip' => $dispositivo->ip,
                    'tipo' => $dispositivo->tipo,
                    'tipo_label' => $dispositivo->tipo_label,
                    'icono' => $dispositivo->icono,
                    'oficina' => $dispositivo->oficina,
                    'piso' => $dispositivo->piso,
                    'monitoreo_habilitado' => $dispositivo->monitoreo_habilitado,
                    // Preferir el dato en vivo por SNMP (sysDescr) sobre el cargado
                    // a mano en dispositivos_edificio, que puede haber quedado viejo.
                    'sistema_operativo' => $lectura['sistema_operativo'] ?? $dispositivo->sistema_operativo,
                    'estado' => $estado,
                    'latencia_ms' => $lectura['latencia_ms'] ?? null,
                    'cpu_pct' => $lectura['cpu_pct'] ?? null,
                    'cpu_modelo' => $lectura['cpu_modelo'] ?? null,
                    'uptime_segundos' => $lectura['uptime_segundos'] ?? null,
                    'ram_pct' => $lectura['ram_pct'] ?? null,
                    'ram_total_gb' => $lectura['ram_total_gb'] ?? null,
                    'ram_usado_gb' => $lectura['ram_usado_gb'] ?? null,
                    'disco_pct' => $lectura['disco_pct'] ?? null,
                    'disco_total_gb' => $lectura['disco_total_gb'] ?? null,
                    'disco_usado_gb' => $lectura['disco_usado_gb'] ?? null,
                ];
            })
            ->values();

        return response()->json([
            'consultado_en' => $cache['consultado_en'] ?? null,
            'dispositivos' => $dispositivos,
        ]);
    }

    /**
     * Refresco on-demand de un único dispositivo (botón "refrescar" de la UI).
     * No espera al ciclo del comando programado; actualiza también el caché
     * general para que el próximo poll de la pantalla ya vea el dato nuevo.
     */
    public function refrescarDispositivo(DispositivoEdificio $dispositivo, SnmpService $snmp): JsonResponse
    {
        if (!$dispositivo->monitoreo_habilitado) {
            return response()->json(['ok' => false, 'error' => 'El monitoreo de este dispositivo está pausado.'], 422);
        }

        if (!SnmpService::esIpMonitoreable($dispositivo->ip)) {
            return response()->json(['ok' => false, 'error' => 'IP inválida, no se puede monitorear.'], 422);
        }

        $lectura = $snmp->relevarDispositivo($dispositivo);
        $lectura['estado'] = SnmpService::clasificarEstado($lectura, SnmpService::umbralesConfigurados());

        $cache = Cache::get(SnmpService::CACHE_KEY_ESTADO, ['dispositivos' => [], 'consultado_en' => null]);
        $dispositivos = collect($cache['dispositivos'])->keyBy('id');
        $dispositivos->put($dispositivo->id, $lectura);
        Cache::put(SnmpService::CACHE_KEY_ESTADO, [
            'dispositivos' => $dispositivos->values()->all(),
            'consultado_en' => $cache['consultado_en'],
        ], now()->addMinutes(30));

        return response()->json(['ok' => true, 'lectura' => $lectura]);
    }

    /**
     * Prende/apaga el monitoreo de un dispositivo puntual (botón en la card).
     * No lo saca del inventario ni de la pantalla, solo evita que el comando
     * programado y el refresco manual lo consulten mientras está pausado.
     */
    public function toggleMonitoreo(DispositivoEdificio $dispositivo): JsonResponse
    {
        $dispositivo->monitoreo_habilitado = !$dispositivo->monitoreo_habilitado;
        $dispositivo->save();

        if (!$dispositivo->monitoreo_habilitado) {
            $cache = Cache::get(SnmpService::CACHE_KEY_ESTADO, ['dispositivos' => [], 'consultado_en' => null]);
            $dispositivos = collect($cache['dispositivos'])->keyBy('id');
            $dispositivos->forget($dispositivo->id);
            Cache::put(SnmpService::CACHE_KEY_ESTADO, [
                'dispositivos' => $dispositivos->values()->all(),
                'consultado_en' => $cache['consultado_en'],
            ], now()->addMinutes(30));
        }

        return response()->json(['ok' => true, 'monitoreo_habilitado' => $dispositivo->monitoreo_habilitado]);
    }

    /**
     * Estado CCTV (LibreNMS): última lectura de CPU de las PCs de operadores
     * de video, cacheada por el comando librenms:monitorear-cpu. Devuelve los
     * puestos con mayor carga, ya ordenados de mayor a menor.
     */
    public function estadoCctv(): JsonResponse
    {
        $cpu = Cache::get(LibreNmsService::CACHE_KEY_ULTIMO_USO);
        $camaras = Cache::get(LibreNmsService::CACHE_KEY_CAMARAS);

        $umbral = (int) config('librenms.umbral_cpu');

        $respuestaCpu = ['disponible' => false];
        if (!empty($cpu['dispositivos'])) {
            $respuestaCpu = [
                'disponible'    => true,
                'umbral'        => $umbral,
                'consultado_en' => $cpu['consultado_en'] ?? null,
                'puestos'       => array_slice($cpu['dispositivos'], 0, 6),
                'en_alerta'     => count(array_filter($cpu['dispositivos'], fn (array $d) => $d['promedio'] > $umbral)),
                'total'         => count($cpu['dispositivos']),
            ];
        }

        $respuestaCamaras = ['disponible' => false];
        if (!empty($camaras['total'])) {
            $respuestaCamaras = [
                'disponible'    => true,
                'total'         => $camaras['total'],
                'caidas'        => count($camaras['offline']),
                'offline'       => array_slice($camaras['offline'], 0, 15),
                'consultado_en' => $camaras['consultado_en'] ?? null,
            ];
        }

        return response()->json([
            // Compatibilidad con el poller de la pantalla: claves de CPU al nivel raíz.
            ...$respuestaCpu,
            'camaras' => $respuestaCamaras,
        ]);
    }

    /**
     * Estado de los troncales SIP de la central telefonica 911 (panel SSW):
     * última lectura cacheada por central-telefonica:monitorear-troncales.
     */
    public function estadoTroncalesCentralTelefonica(): JsonResponse
    {
        $datos = Cache::get(CentralTelefonicaTroncalesService::CACHE_KEY);

        if (empty($datos['troncales'])) {
            return response()->json(['disponible' => false]);
        }

        return response()->json([
            'disponible' => true,
            'troncales' => $datos['troncales'],
            'caidos' => count(array_filter($datos['troncales'], fn (array $t) => $t['estado'] !== 'online')),
            'consultado_en' => $datos['consultado_en'] ?? null,
        ]);
    }

    public function workersStatus(): JsonResponse
    {
        $conflictosInventario = [
            'total' => 0,
            'armas' => 0,
            'chalecos' => 0,
            'detalle' => [],
        ];
        $discrepanciasInventario = [
            'total' => 0,
            'armas' => 0,
            'chalecos' => 0,
            'detalle' => [],
        ];

        if (request()->user()?->can('ver-menu-armamento')) {
            $conflictos = InventarioConflicto::where('estado', InventarioConflicto::ESTADO_ACTIVO)
                ->orderBy('tipo')
                ->orderBy('identificador')
                ->get();
            $conflictosInventario = [
                'total' => $conflictos->count(),
                'armas' => $conflictos->where('tipo', InventarioConflicto::TIPO_ARMA)->count(),
                'chalecos' => $conflictos->where('tipo', InventarioConflicto::TIPO_CHALECO)->count(),
                'detalle' => $conflictos->map(fn (InventarioConflicto $conflicto): array => [
                    'tipo' => $conflicto->tipo,
                    'identificador' => $conflicto->identificador,
                    'detectado_en' => $conflicto->detectado_en?->format('d/m/Y H:i'),
                    'ultima_deteccion_en' => $conflicto->ultima_deteccion_en?->format('d/m/Y H:i'),
                    'funcionarios' => $conflicto->detalles['funcionarios'] ?? [],
                ])->values(),
            ];

            $discrepancias = InventarioDiscrepancia::with(['personal', 'corregidoPor'])
                ->where('estado', InventarioDiscrepancia::ESTADO_ACTIVA)
                ->orderBy('tipo')
                ->orderByDesc('ultima_deteccion_en')
                ->get();
            $discrepanciasInventario = [
                'total' => $discrepancias->count(),
                'armas' => $discrepancias->where('tipo', InventarioDiscrepancia::TIPO_ARMA)->count(),
                'chalecos' => $discrepancias->where('tipo', InventarioDiscrepancia::TIPO_CHALECO)->count(),
                'detalle' => $discrepancias->map(fn (InventarioDiscrepancia $discrepancia): array => [
                    'tipo' => $discrepancia->tipo,
                    'valor_local' => $discrepancia->valor_local,
                    'valor_importado' => $discrepancia->valor_importado,
                    'detectado_en' => $discrepancia->detectado_en?->format('d/m/Y H:i'),
                    'ultima_deteccion_en' => $discrepancia->ultima_deteccion_en?->format('d/m/Y H:i'),
                    'motivo' => $discrepancia->motivo,
                    'corregido_por' => $discrepancia->corregidoPor?->name,
                    'funcionario' => $discrepancia->personal ? [
                        'lp' => $discrepancia->personal->lp,
                        'apellido' => $discrepancia->personal->apellido,
                        'nombre' => $discrepancia->personal->nombre,
                        'jerarquia' => $discrepancia->personal->jerarquia,
                    ] : ($discrepancia->detalles['funcionario'] ?? []),
                ])->values(),
            ];
        }

        try {
            // Verificar que las tablas existen antes de consultarlas
            if (!DB::getSchemaBuilder()->hasTable('jobs')) {
                return response()->json([
                    'error' => 'tabla_jobs_inexistente',
                    'mensaje' => 'Ejecutar: php artisan queue:table && php artisan migrate',
                    'inventario_conflictos' => $conflictosInventario,
                    'inventario_discrepancias' => $discrepanciasInventario,
                ], 200);
            }

            $pendientes = DB::table('jobs')->whereNull('reserved_at')->count();
            $procesando = DB::table('jobs')->whereNotNull('reserved_at')->count();
            $fallidos = DB::getSchemaBuilder()->hasTable('failed_jobs')
                ? DB::table('failed_jobs')->count()
                : 0;

            // Desglose por tipo de job
            $jobsPorTipo = DB::table('jobs')
                ->selectRaw("
                    CASE
                        WHEN payload LIKE '%ProcesarArchivoEventoCecoco%' THEN 'Importación Excel'
                        WHEN payload LIKE '%GeocodificarLoteEventosCecoco%' THEN 'Geocodificación'
                        WHEN payload LIKE '%IndexarArchivoMbox%' THEN 'Indexación de Correos'
                        WHEN payload LIKE '%GenerarBackupBaseDatos%' OR payload LIKE '%RestaurarBackupBaseDatos%' THEN 'Backup/Restore de BD'
                        ELSE 'Otro'
                    END AS tipo,
                    COUNT(*) as total,
                    SUM(CASE WHEN reserved_at IS NOT NULL THEN 1 ELSE 0 END) as procesando
                ")
                ->groupByRaw("
                    CASE
                        WHEN payload LIKE '%ProcesarArchivoEventoCecoco%' THEN 'Importación Excel'
                        WHEN payload LIKE '%GeocodificarLoteEventosCecoco%' THEN 'Geocodificación'
                        WHEN payload LIKE '%IndexarArchivoMbox%' THEN 'Indexación de Correos'
                        WHEN payload LIKE '%GenerarBackupBaseDatos%' OR payload LIKE '%RestaurarBackupBaseDatos%' THEN 'Backup/Restore de BD'
                        ELSE 'Otro'
                    END
                ")
                ->get();

            // Geocodificación: se lee del caché pre-calculado por el schedule (nunca bloquea el request)
            $geoCounts = Cache::get('dashboard_geo_counts');
            $totalDirecciones = $geoCounts[0] ?? null;
            $geocodeadas = $geoCounts[1] ?? null;

            // Estado del servidor Nominatim. Se cachea 55s para no disparar una
            // llamada HTTP en cada poll de la pantalla (que refresca cada 60s).
            $geoServicioOnline = Cache::remember(
                'dashboard_geo_servicio_online',
                55,
                fn () => app(GeocodificacionService::class)->nominatimDisponible()
            );

            // Worker activo: si hay jobs siendo procesados ahora mismo, o si se reservaron hace menos de 10 min
            $workerActivo = $procesando > 0 || DB::table('jobs')
                ->where('reserved_at', '>=', now()->subMinutes(10)->timestamp)
                ->exists();

            // Cola 'mbox' (indexación de backups de correo): se mide aparte porque corre en
            // un worker propio (queue:work mbox --queue=mbox) y puede estar caído sin que el
            // indicador general de arriba lo note, si el worker de 'default' sigue activo.
            $pendientesMbox = DB::table('jobs')->where('queue', 'mbox')->whereNull('reserved_at')->count();
            $procesandoMbox = DB::table('jobs')->where('queue', 'mbox')->whereNotNull('reserved_at')->count();
            $workerActivoMbox = $procesandoMbox > 0 || DB::table('jobs')
                ->where('queue', 'mbox')
                ->where('reserved_at', '>=', now()->subMinutes(10)->timestamp)
                ->exists();

            // Cola 'backups' (backups/restore de la BD desde Configuración del Sistema):
            // mismo motivo que 'mbox' — corre en un worker propio
            // (queue:work backups --queue=backups) que puede estar caído sin que el
            // indicador general de arriba lo note.
            $pendientesBackups = DB::table('jobs')->where('queue', 'backups')->whereNull('reserved_at')->count();
            $procesandoBackups = DB::table('jobs')->where('queue', 'backups')->whereNotNull('reserved_at')->count();
            $workerActivoBackups = $procesandoBackups > 0 || DB::table('jobs')
                ->where('queue', 'backups')
                ->where('reserved_at', '>=', now()->subMinutes(10)->timestamp)
                ->exists();

            // Tamaño BD restauraciones CECOCO: caché que refresca el schedule horario.
            $tamanoRest = Cache::get(CecocoExpedienteService::CACHE_KEY_TAMANO_RESTAURACIONES);
            $tamanoRestGps = Cache::get(CecocoExpedienteService::CACHE_KEY_TAMANO_RESTAURACIONES_GPS);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }

        return response()->json([
            'worker_activo' => $workerActivo,
            'pendientes' => $pendientes,
            'procesando' => $procesando,
            'fallidos' => $fallidos,
            'jobs_por_tipo' => $jobsPorTipo,
            'mbox_worker_activo' => $workerActivoMbox,
            'mbox_pendientes' => $pendientesMbox,
            'mbox_procesando' => $procesandoMbox,
            'backups_worker_activo' => $workerActivoBackups,
            'backups_pendientes' => $pendientesBackups,
            'backups_procesando' => $procesandoBackups,
            'geo_total_dir' => $totalDirecciones,
            'geo_cacheadas' => $geocodeadas,
            'geo_pendientes' => ($totalDirecciones !== null && $geocodeadas !== null) ? max(0, $totalDirecciones - $geocodeadas) : null,
            'geo_servicio_online' => $geoServicioOnline,
            'geo_servicio_motor' => config('services.google.geocoding_enabled', false) ? 'Google' : 'Nominatim',
            'restauraciones_mb' => $tamanoRest['mb'] ?? null,
            'restauraciones_consultado_en' => $tamanoRest['consultado_en'] ?? null,
            'restauraciones_umbral_mb' => config('cecoco.umbral_restauraciones_mb'),
            'restauraciones_gps_mb' => $tamanoRestGps['mb'] ?? null,
            'restauraciones_gps_consultado_en' => $tamanoRestGps['consultado_en'] ?? null,
            'restauraciones_gps_umbral_mb' => config('cecoco.umbral_restauraciones_mb'),
            'restauraciones_gps_restauradas' => Cache::get(CecocoExpedienteService::CACHE_KEY_FICHEROS_RESTAURADOS_GPS, []),
            'inventario_conflictos' => $conflictosInventario,
            'inventario_discrepancias' => $discrepanciasInventario,
        ]);
    }

    public function refreshRestauracionesCache(): JsonResponse
    {
        $cached = Cache::get(CecocoExpedienteService::CACHE_KEY_TAMANO_RESTAURACIONES);
        $consultadoEnAnterior = $cached['consultado_en'] ?? null;

        try {
            ConsultarTamanoRestauracionesCecoco::dispatch();
        } catch (\Throwable $e) {
            Log::error('No se pudo encolar ConsultarTamanoRestauracionesCecoco', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'ok' => false,
                'error' => 'No se pudo encolar la consulta. Verificá el estado del worker.',
            ], 503);
        }

        return response()->json([
            'ok' => true,
            'consultado_en_anterior' => $consultadoEnAnterior,
            'umbral' => config('cecoco.umbral_restauraciones_mb'),
            'mensaje' => 'Consulta encolada. El valor se actualizará en breve.',
        ]);
    }

    public function refreshRestauracionesGpsCache(): JsonResponse
    {
        $cached = Cache::get(CecocoExpedienteService::CACHE_KEY_TAMANO_RESTAURACIONES_GPS);
        $consultadoEnAnterior = $cached['consultado_en'] ?? null;

        try {
            ConsultarTamanoRestauracionesCecoco::dispatch(true);
        } catch (\Throwable $e) {
            Log::error('No se pudo encolar ConsultarTamanoRestauracionesCecoco GPS', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'ok' => false,
                'error' => 'No se pudo encolar la consulta. Verificá el estado del worker.',
            ], 503);
        }

        return response()->json([
            'ok' => true,
            'consultado_en_anterior' => $consultadoEnAnterior,
            'umbral' => config('cecoco.umbral_restauraciones_mb'),
            'mensaje' => 'Consulta encolada. El valor se actualizará en breve.',
        ]);
    }
}
