<?php

namespace App\Console\Commands;

use App\Models\DetalleExpedienteCecoco;
use App\Services\ResumenEventoIaService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Genera con IA los resúmenes de eventos que quedaron en estado "pendiente"
 * (encolados desde la pantalla). Pensado para correr cada minuto, igual que
 * rag:procesar-pendientes, desacoplando la inferencia lenta del request HTTP.
 */
class ResumirPendientesCecoco extends Command
{
    protected $signature = 'cecoco:resumir-pendientes
                            {--limite=5 : Máximo de resúmenes a generar por corrida}';

    protected $description = 'Genera con IA los resúmenes de eventos CECOCO encolados (estado pendiente)';

    public function handle(ResumenEventoIaService $servicio): int
    {
        if (!config('ia.enabled')) {
            return self::SUCCESS;
        }

        $limite = max(1, (int) $this->option('limite'));

        // Tomar pendientes y también "procesando" que quedaron colgados (>30 min,
        // holgura sobre los ~8 min que puede tardar gemma4:e4b en frío): si el
        // proceso anterior murió, hay que reintentarlos.
        $pendientes = DetalleExpedienteCecoco::query()
            ->whereNotNull('detalle_json')
            ->where(function ($q) {
                $q->where('resumen_ia_estado', 'pendiente')
                    ->orWhere(function ($q2) {
                        $q2->where('resumen_ia_estado', 'procesando')
                            ->where('updated_at', '<', Carbon::now()->subMinutes(30));
                    });
            })
            ->orderBy('updated_at')
            ->limit($limite)
            ->get();

        if ($pendientes->isEmpty()) {
            return self::SUCCESS;
        }

        $this->info('Resúmenes pendientes a procesar: ' . $pendientes->count());

        foreach ($pendientes as $detalle) {
            // Marcar procesando (lock optimista: refresca updated_at).
            $detalle->update(['resumen_ia_estado' => 'procesando']);

            try {
                $resumen = $servicio->resumir($detalle->detalle_json);

                $detalle->update([
                    'resumen_ia' => $resumen,
                    'resumen_ia_generado_en' => now(),
                    'resumen_ia_estado' => 'completado',
                    'resumen_ia_error' => null,
                ]);

                $this->line("  ✓ {$detalle->nro_expediente}");
            } catch (\Throwable $e) {
                $detalle->update([
                    'resumen_ia_estado' => 'error',
                    'resumen_ia_error' => $e->getMessage(),
                ]);

                Log::warning('cecoco:resumir-pendientes: error', [
                    'expediente' => $detalle->nro_expediente,
                    'error' => $e->getMessage(),
                ]);

                $this->warn("  ✗ {$detalle->nro_expediente}: {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }
}
