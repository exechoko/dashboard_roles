<?php

namespace App\Console\Commands;

use App\Models\DetalleExpedienteCecoco;
use App\Models\EventoCecoco;
use App\Services\CecocoExpedienteService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

/**
 * Pre-trae y persiste el detalle completo (acciones, recursos, cierre) de los
 * eventos CECOCO de un día o rango de fechas, reutilizando una única sesión
 * para no abusar del servidor. Pensado para correr después del import diario.
 *
 * Con --workers > 1 reparte los expedientes en franjas y lanza un proceso hijo
 * por franja (cada uno con --shard=indice/total), cada uno con su propia sesión
 * CECOCO logueada con una cuenta dedicada (ver cecoco.prefetch_workers). El
 * proceso padre no procesa expedientes: solo orquesta y agrega el progreso.
 */
class PrefetchDetallesCecoco extends Command
{
    protected $signature = 'cecoco:prefetch-detalles
                            {--fecha=     : Fecha en formato Y-m-d (default: ayer; ignorado si se pasa --desde/--hasta)}
                            {--desde=     : Inicio del rango en formato Y-m-d}
                            {--hasta=     : Fin del rango en formato Y-m-d (default: --desde si no se indica)}
                            {--pausa=200  : Milisegundos de pausa entre expedientes}
                            {--limite=    : Máximo de expedientes a procesar (debug; desactiva --workers)}
                            {--refrescar  : Reconsultar incluso los que ya tienen detalle cacheado}
                            {--workers=1  : Sesiones CECOCO en paralelo (requiere cuentas dedicadas, ver cecoco.prefetch_workers)}
                            {--shard=     : Uso interno (proceso hijo) — franja a procesar, formato "indice/total" (ej. 2/3)}';

    protected $description = 'Pre-trae y guarda en la base el detalle completo de los eventos CECOCO de un día o rango de fechas';

    public function handle(CecocoExpedienteService $servicio): int
    {
        [$fechaInicio, $fechaFin] = $this->resolverRango();

        if ($fechaFin->lt($fechaInicio)) {
            $this->error('--hasta no puede ser anterior a --desde.');
            return self::FAILURE;
        }

        $shard = $this->option('shard');
        if ($shard !== null) {
            return $this->procesarComoHijo($servicio, $fechaInicio, $fechaFin, $shard);
        }

        return $this->procesarComoPadre($servicio, $fechaInicio, $fechaFin);
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolverRango(): array
    {
        if ($this->option('desde')) {
            $fechaInicio = Carbon::parse($this->option('desde'))->startOfDay();
            $fechaFin = $this->option('hasta') ? Carbon::parse($this->option('hasta'))->endOfDay() : $fechaInicio->copy()->endOfDay();
        } else {
            $fecha = $this->option('fecha') ? Carbon::parse($this->option('fecha')) : now()->subDay();
            $fechaInicio = $fecha->copy()->startOfDay();
            $fechaFin = $fecha->copy()->endOfDay();
        }

        return [$fechaInicio, $fechaFin];
    }

    /**
     * Toma el lock global (evita que la corrida diaria programada y un disparo
     * manual desde la vista de importación se pisen) y decide si procesa en un
     * solo hilo (comportamiento original) o reparte el trabajo en workers.
     */
    private function procesarComoPadre(CecocoExpedienteService $servicio, Carbon $fechaInicio, Carbon $fechaFin): int
    {
        $lock = Cache::lock('cecoco:prefetch-detalles:lock', 3600);

        if (!$lock->get()) {
            $this->warn('Ya hay una corrida de cecoco:prefetch-detalles en curso. Se omite esta ejecución.');
            Log::warning('cecoco:prefetch-detalles: omitido, ya hay una corrida en curso.');
            return self::SUCCESS;
        }

        // Descarta un pedido de cancelación de una corrida anterior que haya
        // quedado sin consumir (no debería pasar, pero así una corrida nueva
        // nunca arranca ya cancelada).
        Cache::forget('cecoco:prefetch-detalles:cancelar');

        try {
            $workers = max(1, (int) $this->option('workers'));

            // --limite es una opción de debug pensada para un solo hilo: con
            // varios workers cada uno tomaría hasta $limite, multiplicando el
            // total real. Más simple: en ese caso no paralelizar.
            if ($workers <= 1 || $this->option('limite') !== null) {
                return $this->procesarRango($servicio, $fechaInicio, $fechaFin);
            }

            return $this->procesarConWorkers($fechaInicio, $fechaFin, $workers);
        } finally {
            $lock->release();
        }
    }

    /**
     * Procesa una franja de expedientes como proceso hijo: usa una cuenta CECOCO
     * dedicada (una sesión por worker) y escribe su progreso en una clave propia
     * para que el padre la agregue. No toca el lock global (ya lo tiene el padre).
     */
    private function procesarComoHijo(CecocoExpedienteService $servicio, Carbon $fechaInicio, Carbon $fechaFin, string $shardOpt): int
    {
        if (!preg_match('/^(\d+)\/(\d+)$/', $shardOpt, $m)) {
            $this->error('--shard inválido, formato esperado "indice/total" (ej. 2/3).');
            return self::FAILURE;
        }

        $indice = (int) $m[1];
        $total = (int) $m[2];

        if ($indice < 1 || $total < 1 || $indice > $total) {
            $this->error('--shard fuera de rango.');
            return self::FAILURE;
        }

        return $this->procesarRango($servicio, $fechaInicio, $fechaFin, workerIndex: $indice, shard: ['indice' => $indice, 'total' => $total]);
    }

    /**
     * Lanza un proceso hijo de este mismo comando por cada worker (--shard=i/N),
     * espera a que terminen y agrega el progreso de cada uno bajo la clave de
     * progreso principal, para que la vista no tenga que saber que hay varios
     * procesos corriendo.
     */
    private function procesarConWorkers(Carbon $fechaInicio, Carbon $fechaFin, int $workersPedidos): int
    {
        $credenciales = config('cecoco.prefetch_workers', []);
        $workers = min($workersPedidos, count($credenciales));

        if ($workers < 1) {
            $this->warn('No hay cuentas dedicadas configuradas (CECOCO_USER_PREFETCH_1, ...). Se corre con una sola sesión.');
            return $this->procesarRango(app(CecocoExpedienteService::class), $fechaInicio, $fechaFin);
        }

        if ($workers < $workersPedidos) {
            $this->warn("Se pidieron {$workersPedidos} workers pero solo hay {$workers} cuenta(s) dedicada(s) configurada(s). Se usan {$workers}.");
        }

        $refrescar = (bool) $this->option('refrescar');
        $pausaMs = max(0, (int) $this->option('pausa'));
        $rangoLegible = $fechaInicio->format('d/m/Y') . ' - ' . $fechaFin->format('d/m/Y');

        $total = $this->construirQueryPendientes($fechaInicio, $fechaFin, $refrescar)->count();

        $this->line('========================================');
        $this->line('[' . now()->format('Y-m-d H:i:s') . '] cecoco:prefetch-detalles iniciado');
        $this->info("Rango: {$rangoLegible} | Workers: {$workers} | Pausa: {$pausaMs}ms | Refrescar: " . ($refrescar ? 'sí' : 'no'));
        $this->info("Expedientes a procesar: {$total}");

        if ($total === 0) {
            $this->guardarProgreso('cecoco:prefetch-detalles:progreso', [
                'en_curso' => false,
                'rango' => $rangoLegible,
                'total' => 0,
                'procesados' => 0,
                'ok' => 0,
                'errores' => 0,
                'finalizado_en' => now()->toIso8601String(),
            ]);
            $this->info('Nada pendiente. Fin.');
            $this->line('========================================');
            return self::SUCCESS;
        }

        $iniciadoEn = now()->toIso8601String();
        $this->guardarProgreso('cecoco:prefetch-detalles:progreso', [
            'en_curso' => true,
            'rango' => $rangoLegible,
            'total' => $total,
            'procesados' => 0,
            'ok' => 0,
            'errores' => 0,
            'iniciado_en' => $iniciadoEn,
            'actualizado_en' => $iniciadoEn,
        ]);

        Log::info('cecoco:prefetch-detalles iniciado (multi-worker)', ['rango' => $rangoLegible, 'total' => $total, 'workers' => $workers]);

        $shardKeys = [];
        $procesos = [];

        for ($i = 1; $i <= $workers; $i++) {
            $comando = [
                PHP_BINARY,
                base_path('artisan'),
                'cecoco:prefetch-detalles',
                '--desde=' . $fechaInicio->toDateString(),
                '--hasta=' . $fechaFin->toDateString(),
                '--pausa=' . $pausaMs,
                '--shard=' . $i . '/' . $workers,
            ];
            if ($refrescar) {
                $comando[] = '--refrescar';
            }

            $proceso = new Process($comando, base_path());
            $proceso->setTimeout(3600);
            $proceso->setEnv($this->entornoProceso());
            $proceso->start(function (string $tipo, string $buffer) use ($i): void {
                foreach (explode("\n", trim($buffer)) as $linea) {
                    if ($linea !== '') {
                        $this->line("  [w{$i}] {$linea}");
                    }
                }
            });

            $procesos[] = $proceso;
            $shardKeys[] = "cecoco:prefetch-detalles:progreso:shard:{$i}";
        }

        while (array_filter($procesos, fn (Process $p): bool => $p->isRunning())) {
            usleep(500_000);
            foreach ($procesos as $p) {
                $p->checkTimeout();
            }
            $this->agregarProgreso($shardKeys, $rangoLegible, $total, $iniciadoEn);
        }

        // Recién acá se descarta el flag de cancelación: mientras hay workers
        // corriendo, todos tienen que seguir viéndolo para frenar.
        Cache::forget('cecoco:prefetch-detalles:cancelar');

        [$ok, $errores, $procesados] = $this->agregarProgreso($shardKeys, $rangoLegible, $total, $iniciadoEn, finalizar: true);

        foreach ($shardKeys as $key) {
            Cache::forget($key);
        }

        $fallidos = array_values(array_filter($procesos, fn (Process $p): bool => !$p->isSuccessful()));

        foreach ($fallidos as $p) {
            Log::error('cecoco:prefetch-detalles: un worker terminó con error', [
                'salida_error' => substr($p->getErrorOutput(), -2000),
            ]);
        }

        $this->info("Listo: {$ok} guardados, {$errores} errores ({$procesados}/{$total}) con {$workers} workers.");
        $this->line('========================================');

        Log::info('cecoco:prefetch-detalles completado (multi-worker)', [
            'rango' => $rangoLegible,
            'ok' => $ok,
            'errores' => $errores,
            'workers' => $workers,
            'workers_fallidos' => count($fallidos),
        ]);

        return empty($fallidos) ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Suma el progreso de cada shard y lo guarda bajo la clave de progreso
     * principal, para que la vista siga leyendo una sola clave como antes.
     *
     * @param array<int, string> $shardKeys
     * @return array{0: int, 1: int, 2: int} [ok, errores, procesados]
     */
    private function agregarProgreso(array $shardKeys, string $rangoLegible, int $total, string $iniciadoEn, bool $finalizar = false): array
    {
        $procesados = 0;
        $ok = 0;
        $errores = 0;
        $enCurso = false;
        $workersInfo = [];

        foreach ($shardKeys as $indice => $key) {
            $p = Cache::get($key);
            $workersInfo[] = [
                'worker' => $indice + 1,
                'en_curso' => $finalizar ? false : ($p['en_curso'] ?? false),
                'total' => $p['total'] ?? 0,
                'procesados' => $p['procesados'] ?? 0,
                'ok' => $p['ok'] ?? 0,
                'errores' => $p['errores'] ?? 0,
            ];

            if (!$p) {
                continue;
            }
            $procesados += $p['procesados'] ?? 0;
            $ok += $p['ok'] ?? 0;
            $errores += $p['errores'] ?? 0;
            if ($p['en_curso'] ?? false) {
                $enCurso = true;
            }
        }

        $progreso = [
            'en_curso' => $finalizar ? false : $enCurso,
            'rango' => $rangoLegible,
            'total' => $total,
            'procesados' => $procesados,
            'ok' => $ok,
            'errores' => $errores,
            'iniciado_en' => $iniciadoEn,
            'actualizado_en' => now()->toIso8601String(),
            'workers' => $workersInfo,
        ];

        if ($finalizar) {
            $progreso['finalizado_en'] = now()->toIso8601String();
        }

        $this->guardarProgreso('cecoco:prefetch-detalles:progreso', $progreso);

        return [$ok, $errores, $procesados];
    }

    /**
     * Entorno explícito para el proceso hijo. Bajo el servidor embebido de PHP
     * (`php -S`, usado en desarrollo) `proc_open()` a veces no hereda variables
     * de sistema como `SystemRoot`; sin ellas, algunos binarios en Windows
     * fallan al inicializar Winsock (error 10106) aunque el mismo binario
     * funcione perfecto desde una consola normal.
     *
     * @return array<string, string>
     */
    private function entornoProceso(): array
    {
        $entorno = [];
        foreach (getenv() as $clave => $valor) {
            if (is_string($valor)) {
                $entorno[$clave] = $valor;
            }
        }

        if (PHP_OS_FAMILY === 'Windows' && !isset($entorno['SystemRoot'])) {
            $entorno['SystemRoot'] = getenv('SystemRoot') ?: getenv('windir') ?: 'C:\\Windows';
        }

        return $entorno;
    }

    private function construirQueryPendientes(Carbon $fechaInicio, Carbon $fechaFin, bool $refrescar): Builder
    {
        $query = EventoCecoco::whereBetween('fecha_hora', [$fechaInicio, $fechaFin])
            // Solo eventos cerrados: un evento abierto daría un detalle parcial
            // (sin cierre, timeline incompleto). Esos se traen cuando cierren, en
            // la corrida del día siguiente.
            ->whereNotNull('fecha_cierre');

        if (!$refrescar) {
            // Saltar los que ya tienen detalle persistido
            $query->whereDoesntHave('detalle', function ($q) {
                $q->whereNotNull('detalle_json');
            });
        }

        return $query;
    }

    /**
     * @param array{indice: int, total: int}|null $shard
     */
    private function procesarRango(CecocoExpedienteService $servicio, Carbon $fechaInicio, Carbon $fechaFin, ?int $workerIndex = null, ?array $shard = null): int
    {
        $pausaMs = max(0, (int) $this->option('pausa'));
        $limite = $this->option('limite') !== null ? max(1, (int) $this->option('limite')) : null;
        $refrescar = (bool) $this->option('refrescar');
        $contexto = $fechaInicio->format('Y-m-d') . '..' . $fechaFin->format('Y-m-d');
        $rangoLegible = $fechaInicio->format('d/m/Y') . ' - ' . $fechaFin->format('d/m/Y');
        $progresoKey = $shard ? "cecoco:prefetch-detalles:progreso:shard:{$shard['indice']}" : 'cecoco:prefetch-detalles:progreso';
        $etiqueta = $shard ? " [worker {$shard['indice']}/{$shard['total']}]" : '';

        $this->line('========================================');
        $this->line('[' . now()->format('Y-m-d H:i:s') . "] cecoco:prefetch-detalles{$etiqueta} iniciado");
        $this->info("Rango: {$fechaInicio->format('d/m/Y')} a {$fechaFin->format('d/m/Y')} | Pausa: {$pausaMs}ms | Refrescar: " . ($refrescar ? 'sí' : 'no'));

        $query = $this->construirQueryPendientes($fechaInicio, $fechaFin, $refrescar);

        if ($shard) {
            $query->whereRaw('id % ? = ?', [$shard['total'], $shard['indice'] - 1]);
        }

        $eventos = $query->orderBy('fecha_hora')->get(['id', 'nro_expediente']);

        if ($limite !== null) {
            $eventos = $eventos->take($limite);
        }

        $total = $eventos->count();
        $this->info("Expedientes a procesar{$etiqueta}: {$total}");

        if ($total === 0) {
            $this->guardarProgreso($progresoKey, [
                'en_curso' => false,
                'rango' => $rangoLegible,
                'total' => 0,
                'procesados' => 0,
                'ok' => 0,
                'errores' => 0,
                'finalizado_en' => now()->toIso8601String(),
            ]);
            $this->info('Nada pendiente. Fin.');
            $this->line('========================================');
            return self::SUCCESS;
        }

        $iniciadoEn = now()->toIso8601String();

        $this->guardarProgreso($progresoKey, [
            'en_curso' => true,
            'rango' => $rangoLegible,
            'total' => $total,
            'procesados' => 0,
            'ok' => 0,
            'errores' => 0,
            'iniciado_en' => $iniciadoEn,
            'actualizado_en' => $iniciadoEn,
        ]);

        Log::info("cecoco:prefetch-detalles{$etiqueta} iniciado", ['rango' => $contexto, 'total' => $total]);

        $client = $servicio->iniciarSesionCompartida($workerIndex);
        $ok = 0;
        $errores = 0;
        $consecutivos = 0;
        $t0 = microtime(true);

        foreach ($eventos as $i => $evento) {
            if (Cache::get('cecoco:prefetch-detalles:cancelar')) {
                // Si es un worker hijo, el flag lo descarta el proceso padre una
                // vez que todos terminaron; si corre en un solo hilo, lo descarta acá.
                if (!$shard) {
                    Cache::forget('cecoco:prefetch-detalles:cancelar');
                }
                $this->warn("Cancelado por el usuario en {$i}/{$total}{$etiqueta}.");
                Log::warning('cecoco:prefetch-detalles: cancelado por el usuario', [
                    'rango' => $contexto,
                    'procesados' => $i,
                    'total' => $total,
                ]);
                $this->guardarProgreso($progresoKey, [
                    'en_curso' => false,
                    'cancelado' => true,
                    'rango' => $rangoLegible,
                    'total' => $total,
                    'procesados' => $i,
                    'ok' => $ok,
                    'errores' => $errores,
                    'iniciado_en' => $iniciadoEn,
                    'finalizado_en' => now()->toIso8601String(),
                ]);
                return self::SUCCESS;
            }

            try {
                $detalle = $servicio->obtenerDetalleExpediente((string) $evento->nro_expediente, $client);

                DetalleExpedienteCecoco::updateOrCreate(
                    ['evento_cecoco_id' => $evento->id],
                    [
                        'nro_expediente' => $evento->nro_expediente,
                        'detalle_json' => $detalle,
                        'fecha_consulta' => now(),
                    ]
                );

                $ok++;
                $consecutivos = 0;
            } catch (\Throwable $e) {
                $errores++;
                $consecutivos++;
                Log::warning('cecoco:prefetch-detalles: error en expediente', [
                    'expediente' => $evento->nro_expediente,
                    'error' => $e->getMessage(),
                ]);

                // Ante varios fallos seguidos, la sesión pudo expirar: reintentar login una vez.
                if ($consecutivos >= 3) {
                    $this->warn("  Reiniciando sesión CECOCO tras fallos consecutivos{$etiqueta}…");
                    try {
                        $client = $servicio->iniciarSesionCompartida($workerIndex);
                    } catch (\Throwable $e2) {
                        Log::error('cecoco:prefetch-detalles: no se pudo reiniciar sesión', ['error' => $e2->getMessage()]);
                    }
                    $consecutivos = 0;
                }
            }

            if (($i + 1) % 50 === 0 || ($i + 1) === $total) {
                $this->line('  [' . now()->format('H:i:s') . "]{$etiqueta} " . ($i + 1) . "/{$total} (ok: {$ok}, errores: {$errores})");
                $this->guardarProgreso($progresoKey, [
                    'en_curso' => true,
                    'rango' => $rangoLegible,
                    'total' => $total,
                    'procesados' => $i + 1,
                    'ok' => $ok,
                    'errores' => $errores,
                    'iniciado_en' => $iniciadoEn,
                    'actualizado_en' => now()->toIso8601String(),
                ]);
            }

            if ($pausaMs > 0) {
                usleep($pausaMs * 1000);
            }
        }

        $segundos = round(microtime(true) - $t0);
        $this->info("Listo{$etiqueta}: {$ok} guardados, {$errores} errores en {$segundos}s.");
        $this->line('========================================');

        $this->guardarProgreso($progresoKey, [
            'en_curso' => false,
            'rango' => $rangoLegible,
            'total' => $total,
            'procesados' => $total,
            'ok' => $ok,
            'errores' => $errores,
            'iniciado_en' => $iniciadoEn,
            'finalizado_en' => now()->toIso8601String(),
        ]);

        Log::info("cecoco:prefetch-detalles{$etiqueta} completado", [
            'fecha' => $contexto,
            'ok' => $ok,
            'errores' => $errores,
            'segundos' => $segundos,
        ]);

        return self::SUCCESS;
    }

    /**
     * @param array{en_curso: bool, rango: string, total: int, procesados: int, ok: int, errores: int, cancelado?: bool, iniciado_en?: string, actualizado_en?: string, finalizado_en?: string} $progreso
     */
    private function guardarProgreso(string $key, array $progreso): void
    {
        Cache::put($key, $progreso, now()->addDay());
    }
}
