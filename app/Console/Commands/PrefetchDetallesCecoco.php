<?php

namespace App\Console\Commands;

use App\Models\DetalleExpedienteCecoco;
use App\Models\EventoCecoco;
use App\Services\CecocoExpedienteService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Pre-trae y persiste el detalle completo (acciones, recursos, cierre) de los
 * eventos CECOCO de un día o rango de fechas, reutilizando una única sesión
 * para no abusar del servidor. Pensado para correr después del import diario.
 */
class PrefetchDetallesCecoco extends Command
{
    protected $signature = 'cecoco:prefetch-detalles
                            {--fecha=     : Fecha en formato Y-m-d (default: ayer; ignorado si se pasa --desde/--hasta)}
                            {--desde=     : Inicio del rango en formato Y-m-d}
                            {--hasta=     : Fin del rango en formato Y-m-d (default: --desde si no se indica)}
                            {--pausa=200  : Milisegundos de pausa entre expedientes}
                            {--limite=    : Máximo de expedientes a procesar (debug)}
                            {--refrescar  : Reconsultar incluso los que ya tienen detalle cacheado}';

    protected $description = 'Pre-trae y guarda en la base el detalle completo de los eventos CECOCO de un día o rango de fechas';

    public function handle(CecocoExpedienteService $servicio): int
    {
        if ($this->option('desde')) {
            $fechaInicio = Carbon::parse($this->option('desde'))->startOfDay();
            $fechaFin = $this->option('hasta') ? Carbon::parse($this->option('hasta'))->endOfDay() : $fechaInicio->copy()->endOfDay();
        } else {
            $fecha = $this->option('fecha') ? Carbon::parse($this->option('fecha')) : now()->subDay();
            $fechaInicio = $fecha->copy()->startOfDay();
            $fechaFin = $fecha->copy()->endOfDay();
        }

        if ($fechaFin->lt($fechaInicio)) {
            $this->error('--hasta no puede ser anterior a --desde.');
            return self::FAILURE;
        }

        $pausaMs = max(0, (int) $this->option('pausa'));
        $limite = $this->option('limite') !== null ? max(1, (int) $this->option('limite')) : null;
        $refrescar = (bool) $this->option('refrescar');
        $contexto = $fechaInicio->format('Y-m-d') . '..' . $fechaFin->format('Y-m-d');

        $this->line('========================================');
        $this->line('[' . now()->format('Y-m-d H:i:s') . '] cecoco:prefetch-detalles iniciado');
        $this->info("Rango: {$fechaInicio->format('d/m/Y')} a {$fechaFin->format('d/m/Y')} | Pausa: {$pausaMs}ms | Refrescar: " . ($refrescar ? 'sí' : 'no'));

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

        $eventos = $query->orderBy('fecha_hora')->get(['id', 'nro_expediente']);

        if ($limite !== null) {
            $eventos = $eventos->take($limite);
        }

        $total = $eventos->count();
        $this->info("Expedientes a procesar: {$total}");

        if ($total === 0) {
            $this->info('Nada pendiente. Fin.');
            $this->line('========================================');
            return self::SUCCESS;
        }

        Log::info('cecoco:prefetch-detalles iniciado', ['rango' => $contexto, 'total' => $total]);

        $client = $servicio->iniciarSesionCompartida();
        $ok = 0;
        $errores = 0;
        $consecutivos = 0;
        $t0 = microtime(true);

        foreach ($eventos as $i => $evento) {
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
                    $this->warn('  Reiniciando sesión CECOCO tras fallos consecutivos…');
                    try {
                        $client = $servicio->iniciarSesionCompartida();
                    } catch (\Throwable $e2) {
                        Log::error('cecoco:prefetch-detalles: no se pudo reiniciar sesión', ['error' => $e2->getMessage()]);
                    }
                    $consecutivos = 0;
                }
            }

            if (($i + 1) % 50 === 0) {
                $this->line('  [' . now()->format('H:i:s') . '] ' . ($i + 1) . "/{$total} (ok: {$ok}, errores: {$errores})");
            }

            if ($pausaMs > 0) {
                usleep($pausaMs * 1000);
            }
        }

        $segundos = round(microtime(true) - $t0);
        $this->info("Listo: {$ok} guardados, {$errores} errores en {$segundos}s.");
        $this->line('========================================');

        Log::info('cecoco:prefetch-detalles completado', [
            'fecha' => $contexto,
            'ok' => $ok,
            'errores' => $errores,
            'segundos' => $segundos,
        ]);

        return self::SUCCESS;
    }
}
