<?php

namespace App\Console\Commands;

use App\Jobs\GeocodificarLoteEventosCecoco;
use App\Services\GeocodificacionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GeocodificarHistoricoCecoco extends Command
{
    protected $signature = 'cecoco:geocodificar-historico
                            {--lote=50      : Direcciones por lote / job}
                            {--delay=20     : Segundos de retraso entre lotes encolados}
                            {--pausa=300    : Milisegundos entre llamadas a Google dentro de un lote}
                            {--limit=0      : Limitar a N direcciones (0 = todas)}
                            {--sincrono     : Ejecutar sincrónicamente en lugar de encolar jobs}
                            {--dry-run      : Solo mostrar estadísticas, sin procesar}';

    protected $description = 'Geocodifica el backlog histórico completo de direcciones de eventos CECOCO que nunca fueron intentadas';

    public function handle(GeocodificacionService $geocoder): int
    {
        $tamanoLote    = max(1, (int) $this->option('lote'));
        $delaySegundos = max(0, (int) $this->option('delay'));
        $pausaMs       = max(0, (int) $this->option('pausa'));
        $limit         = max(0, (int) $this->option('limit'));
        $sincrono      = (bool) $this->option('sincrono');
        $dryRun        = (bool) $this->option('dry-run');

        $this->line('========================================');
        $this->line('[' . now()->format('Y-m-d H:i:s') . '] cecoco:geocodificar-historico iniciado');
        $this->info("Lote: {$tamanoLote} | Delay: {$delaySegundos}s | Pausa: {$pausaMs}ms | Modo: " . ($sincrono ? 'síncrono' : 'colas'));

        // ── 1. Obtener direcciones no cacheadas (nunca intentadas) ────────────
        $this->line('Consultando direcciones pendientes...');

        $query = DB::table('evento_cecoco')
            ->whereNotNull('direccion')
            ->where('direccion', '!=', '')
            ->where('direccion', '!=', '-')
            ->whereNotIn(
                DB::raw('TRIM(direccion)'),
                DB::table('geocodificacion_directa')->select('direccion_original')
            )
            ->distinct()
            ->pluck('direccion')
            ->map(fn($d) => trim($d))
            ->unique()
            ->values();

        $totalRaw = $query->count();
        $this->info("Direcciones únicas sin cachear: {$totalRaw}");

        // ── 2. Filtrar inválidas ──────────────────────────────────────────────
        $this->line('Filtrando direcciones inválidas...');
        $pendientes = $query->filter(fn($dir) => $geocoder->esDireccionValida($dir))->values();

        $invalidas = $totalRaw - $pendientes->count();
        $this->warn("Inválidas descartadas (sin número ni intersección): {$invalidas}");

        $totalPendiente = $pendientes->count();
        $this->info("Pendientes válidas a geocodificar: {$totalPendiente}");

        if ($totalPendiente === 0) {
            $this->info('No hay direcciones pendientes. Todo el histórico está geocodificado.');
            $this->line('========================================');
            return self::SUCCESS;
        }

        // ── 3. Aplicar límite opcional ────────────────────────────────────────
        if ($limit > 0 && $limit < $totalPendiente) {
            $pendientes     = $pendientes->take($limit);
            $totalPendiente = $pendientes->count();
            $this->warn("Limitado a {$totalPendiente} direcciones (--limit={$limit})");
        }

        // ── 4. Estimaciones ───────────────────────────────────────────────────
        $totalLotes         = (int) ceil($totalPendiente / $tamanoLote);
        $tiempoApiSeg       = ($totalPendiente * $pausaMs) / 1000;
        $tiempoDelaySeg     = ($totalLotes - 1) * $delaySegundos;
        $tiempoTotalMin     = round(($tiempoApiSeg + $tiempoDelaySeg) / 60, 1);
        $costoEstimado      = round($totalPendiente / 1000 * 0.005, 2); // Google: USD 5 por 1000 reqs

        $this->line('');
        $this->line("  Lotes a encolar : {$totalLotes}");
        $this->line("  Tiempo estimado : ~{$tiempoTotalMin} min");
        $this->line("  Costo estimado  : ~USD {$costoEstimado} (Google Maps API)");
        $this->line('');

        if ($dryRun) {
            $this->warn('Dry-run: no se procesó nada.');
            $this->line('========================================');
            return self::SUCCESS;
        }

        if (!$this->confirm("¿Continuar geocodificando {$totalPendiente} direcciones en {$totalLotes} lotes?", true)) {
            $this->warn('Cancelado por el usuario.');
            return self::SUCCESS;
        }

        // ── 5. Procesar ───────────────────────────────────────────────────────
        $lotes   = $pendientes->chunk($tamanoLote)->map(fn($c) => $c->values()->all())->all();
        $contexto = 'historico-' . now()->format('Y-m-d');

        Log::info('cecoco:geocodificar-historico iniciado', [
            'total_pendiente' => $totalPendiente,
            'total_lotes'     => $totalLotes,
            'sincrono'        => $sincrono,
        ]);

        if ($sincrono) {
            $this->procesarSincrono($lotes, $geocoder, $pausaMs, $contexto);
        } else {
            $this->encolarLotes($lotes, $pausaMs, $delaySegundos, $contexto);
        }

        $this->line('========================================');

        Log::info('cecoco:geocodificar-historico completado (lotes despachados)', [
            'total_lotes' => $totalLotes,
            'contexto'    => $contexto,
        ]);

        return self::SUCCESS;
    }

    private function procesarSincrono(array $lotes, GeocodificacionService $geocoder, int $pausaMs, string $contexto): void
    {
        $totalLotes  = count($lotes);
        $geocodeadas = 0;
        $fallidas    = 0;

        foreach ($lotes as $i => $lote) {
            $num = $i + 1;
            $this->line('[' . now()->format('H:i:s') . "] Lote {$num}/{$totalLotes} (" . count($lote) . ' dir.)...');

            foreach ($lote as $direccion) {
                try {
                    $resultado = $geocoder->geocodificar($direccion);
                    $resultado ? $geocodeadas++ : $fallidas++;
                } catch (\Exception $e) {
                    $fallidas++;
                    Log::warning('cecoco:geocodificar-historico síncrono: error', [
                        'direccion' => $direccion,
                        'error'     => $e->getMessage(),
                    ]);
                }
                if ($pausaMs > 0) {
                    usleep($pausaMs * 1000);
                }
            }

            $pct = round($num / $totalLotes * 100);
            $this->line("  → Geocodeadas: {$geocodeadas} | Fallidas: {$fallidas} | {$pct}%");
        }

        $this->info("Síncrono finalizado. Geocodeadas: {$geocodeadas} | Fallidas: {$fallidas}");
    }

    private function encolarLotes(array $lotes, int $pausaMs, int $delaySegundos, string $contexto): void
    {
        $totalLotes = count($lotes);

        foreach ($lotes as $i => $lote) {
            $num      = $i + 1;
            $retraso  = $i * $delaySegundos;
            $ejecutaA = now()->addSeconds($retraso)->format('H:i:s');

            GeocodificarLoteEventosCecoco::dispatch($lote, $pausaMs, $contexto)
                ->delay(now()->addSeconds($retraso));

            $this->line("  Lote {$num}/{$totalLotes} → ~{$ejecutaA} (" . count($lote) . " dir., delay {$retraso}s)");
        }

        $this->info("{$totalLotes} lotes encolados correctamente.");
    }
}
