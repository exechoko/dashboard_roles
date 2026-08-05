<?php

namespace App\Console\Commands;

use App\Models\ActivacionTotem;
use App\Services\SubidaVideoTotemService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Procesa los videos de tótem subidos desde la pantalla: hashea el archivo
 * ya recibido en disco local y lo copia a la carpeta de red del tótem
 * correspondiente. Desacopla ese trabajo (potencialmente lento por la copia
 * de red) del request HTTP que recibió la subida. Pensado para correr cada
 * minuto, igual que cecoco:resumir-pendientes.
 */
class ProcesarVideosTotemPendientes extends Command
{
    protected $signature = 'totem:procesar-videos-pendientes
                            {--limite=5 : Máximo de videos a procesar por corrida}';

    protected $description = 'Hashea y copia a la carpeta de red los videos de tótem subidos y encolados';

    public function handle(SubidaVideoTotemService $servicio): int
    {
        $limite = max(1, (int) $this->option('limite'));

        // Tomar pendientes y también "procesando" que quedaron trabados (>15 min,
        // holgura sobre lo que tarda hashear y copiar ~60MB): si el proceso
        // anterior murió a mitad de camino, hay que reintentarlos.
        $pendientes = ActivacionTotem::query()
            ->where(function ($q) {
                $q->where('subida_estado', ActivacionTotem::SUBIDA_PENDIENTE)
                    ->orWhere(function ($q2) {
                        $q2->where('subida_estado', ActivacionTotem::SUBIDA_PROCESANDO)
                            ->where('updated_at', '<', Carbon::now()->subMinutes(15));
                    });
            })
            ->orderBy('updated_at')
            ->limit($limite)
            ->get();

        if ($pendientes->isEmpty()) {
            return self::SUCCESS;
        }

        $this->info('Videos pendientes a procesar: ' . $pendientes->count());

        foreach ($pendientes as $activacion) {
            $activacion->update(['subida_estado' => ActivacionTotem::SUBIDA_PROCESANDO]);

            try {
                $resultado = $servicio->procesar($activacion);

                $activacion->update([
                    'ruta_archivo' => $resultado['ruta_archivo'],
                    'hash_sha256' => $resultado['hash_sha256'],
                    'subida_estado' => ActivacionTotem::SUBIDA_COMPLETADO,
                    'subida_error' => null,
                    'estado' => ActivacionTotem::ESTADO_DESCARGADO,
                    'fecha_descarga' => now(),
                ]);

                // Recién ahora, con el resultado ya guardado en la base, se borra
                // el temporal: si algo falla antes de esta línea, el archivo sigue
                // disponible para el próximo intento.
                @unlink($servicio->rutaTemporal($activacion));

                $this->line("  ✓ {$activacion->nro_expediente}");
            } catch (\Throwable $e) {
                $activacion->update([
                    'subida_estado' => ActivacionTotem::SUBIDA_ERROR,
                    'subida_error' => $e->getMessage(),
                ]);

                Log::warning('totem:procesar-videos-pendientes: error', [
                    'activacion_id' => $activacion->id,
                    'expediente' => $activacion->nro_expediente,
                    'error' => $e->getMessage(),
                ]);

                $this->warn("  ✗ {$activacion->nro_expediente}: {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }
}
