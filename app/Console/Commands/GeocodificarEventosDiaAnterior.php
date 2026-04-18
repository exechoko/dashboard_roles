<?php

namespace App\Console\Commands;

use App\Jobs\GeocodificarLoteEventosCecoco;
use App\Models\EventoCecoco;
use App\Models\GeocodificacionDirecta;
use App\Services\GeocodificacionService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Pre-geocodifica en lotes las direcciones de los eventos CECOCO de un día dado
 * para el mapa de calor, evitando saturar la API de Google Maps.
 *
 * La lógica de resolución de direcciones replica la de EventoCecocoController::mapaCalorDatos():
 *  1. Agrupar eventos por 'direccion'.
 *  2. Si la dirección no tiene numeración, intentar extraerla de la descripción.
 *  3. Geocodificar la dirección resuelta (el service guarda el resultado en caché).
 */
class GeocodificarEventosDiaAnterior extends Command
{
    protected $signature = 'cecoco:geocodificar-dia-anterior
                            {--fecha=        : Fecha en formato Y-m-d (default: ayer)}
                            {--lote=50       : Direcciones por lote / job}
                            {--delay=20      : Segundos de retraso entre lotes encolados}
                            {--pausa=300     : Milisegundos entre llamadas a Google dentro de un lote (300 ms = ~3 RPS)}
                            {--sincrono      : Ejecutar sincrónicamente en lugar de encolar jobs}';

    protected $description = 'Geocodifica en lotes (sin saturar Google API) las direcciones de los eventos CECOCO del día anterior para el mapa de calor';

    public function handle(GeocodificacionService $geocoder): int
    {
        $fecha         = $this->option('fecha')
            ? Carbon::parse($this->option('fecha'))
            : now()->subDay();

        $tamanoLote    = max(1, (int) $this->option('lote'));
        $delaySegundos = max(0, (int) $this->option('delay'));
        $pausaMs       = max(0, (int) $this->option('pausa'));
        $sincrono      = (bool) $this->option('sincrono');
        $contexto      = $fecha->format('Y-m-d');

        $this->line('========================================');
        $this->line('[' . now()->format('Y-m-d H:i:s') . '] cecoco:geocodificar-dia-anterior iniciado');
        $this->info("Fecha: {$fecha->format('d/m/Y')} | Lote: {$tamanoLote} dir. | Delay: {$delaySegundos}s | Pausa: {$pausaMs}ms | Modo: " . ($sincrono ? 'síncrono' : 'colas'));

        Log::info('cecoco:geocodificar-dia-anterior iniciado', [
            'fecha'    => $contexto,
            'lote'     => $tamanoLote,
            'delay'    => $delaySegundos,
            'pausa_ms' => $pausaMs,
            'sincrono' => $sincrono,
        ]);

        // ── 1. Obtener eventos del día agrupados por dirección ────────────────
        $gruposDireccion = EventoCecoco::whereBetween('fecha_hora', [
                $fecha->copy()->startOfDay(),
                $fecha->copy()->endOfDay(),
            ])
            ->whereNotNull('direccion')
            ->where('direccion', '!=', '')
            ->where('direccion', '!=', '-')
            ->selectRaw('direccion, MIN(descripcion) as descripcion_muestra')
            ->groupBy('direccion')
            ->get();

        $totalGrupos = $gruposDireccion->count();
        $this->info("Grupos de dirección en el día: {$totalGrupos}");

        if ($totalGrupos === 0) {
            $this->warn('No hay eventos con dirección para geocodificar.');
            return self::SUCCESS;
        }

        // ── 2. Filtrar las que tienen formato de dirección válido ─────────────
        $direccionesResueltas = [];
        $omitidas = 0;

        foreach ($gruposDireccion as $grupo) {
            $dir = trim($grupo->direccion);

            if (!$geocoder->esDireccionValida($dir)) {
                $omitidas++;
                continue;
            }

            $direccionesResueltas[] = $dir;
        }

        if ($omitidas > 0) {
            $this->warn("Direcciones inválidas omitidas: {$omitidas} (sin número ni intersección)");
        }

        // Eliminar duplicados que puedan surgir de la extracción
        $direccionesResueltas = array_values(array_unique($direccionesResueltas));
        $this->info('Direcciones únicas resueltas: ' . count($direccionesResueltas));

        // ── 3. Filtrar las que ya están en caché ──────────────────────────────
        $yaCacheadas = GeocodificacionDirecta::whereIn('direccion_original', $direccionesResueltas)
            ->pluck('direccion_original')
            ->all();

        $pendientes    = array_values(array_diff($direccionesResueltas, $yaCacheadas));
        $totalPendiente = count($pendientes);

        $this->info('Ya en caché: ' . count($yaCacheadas) . ' | Pendientes: ' . $totalPendiente);

        if ($totalPendiente === 0) {
            $this->info('Todas las direcciones ya están geocodificadas. Nada que hacer.');
            $this->line('========================================');
            return self::SUCCESS;
        }

        // ── 4. Dividir en lotes y ejecutar / encolar ──────────────────────────
        $lotes      = array_chunk($pendientes, $tamanoLote);
        $totalLotes = count($lotes);
        $this->info("Lotes: {$totalLotes} (× {$tamanoLote} dir./lote)");

        if ($sincrono) {
            $this->geocodificarSincrono($lotes, $geocoder, $pausaMs, $contexto);
        } else {
            $this->encolarLotes($lotes, $pausaMs, $delaySegundos, $contexto);
        }

        $this->line('========================================');

        Log::info('cecoco:geocodificar-dia-anterior completado', [
            'fecha'           => $contexto,
            'total_grupos'    => $totalGrupos,
            'pendientes'      => $totalPendiente,
            'total_lotes'     => $totalLotes,
            'sincrono'        => $sincrono,
        ]);

        return self::SUCCESS;
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function geocodificarSincrono(array $lotes, GeocodificacionService $geocoder, int $pausaMs, string $contexto): void
    {
        $totalLotes = count($lotes);

        foreach ($lotes as $i => $lote) {
            $num = $i + 1;
            $this->line('[' . now()->format('H:i:s') . "] Lote {$num}/{$totalLotes} (" . count($lote) . ' dir.)...');

            foreach ($lote as $direccion) {
                try {
                    $geocoder->geocodificar($direccion);
                } catch (\Exception $e) {
                    Log::warning('cecoco:geocodificar-dia-anterior síncrono: error', [
                        'contexto'  => $contexto,
                        'direccion' => $direccion,
                        'error'     => $e->getMessage(),
                    ]);
                }
                if ($pausaMs > 0) {
                    usleep($pausaMs * 1000);
                }
            }
        }

        $this->info('Geocodificación síncrona finalizada.');
    }

    private function encolarLotes(array $lotes, int $pausaMs, int $delaySegundos, string $contexto): void
    {
        $totalLotes = count($lotes);

        foreach ($lotes as $i => $lote) {
            $num           = $i + 1;
            $retraso       = $i * $delaySegundos;
            $ejecutaA      = now()->addSeconds($retraso)->format('H:i:s');

            GeocodificarLoteEventosCecoco::dispatch($lote, $pausaMs, $contexto)
                ->delay(now()->addSeconds($retraso));

            $this->line("  Lote {$num}/{$totalLotes} encolado → ejecuta ~{$ejecutaA} ({" . count($lote) . "} dir., delay {$retraso}s)");
        }

        $tiempoEstimado = (($totalLotes - 1) * $delaySegundos) + (int) (count($lotes[count($lotes) - 1]) * $pausaMs / 1000);
        $this->info("Todos los lotes encolados. Tiempo estimado total: ~{$tiempoEstimado}s");
    }
}
