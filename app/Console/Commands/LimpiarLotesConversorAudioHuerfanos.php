<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Borra las carpetas de conversor_audio_temp/{user}/{token} que quedaron
 * abandonadas (el usuario cerró la pestaña antes de pedir el ZIP final, ver
 * ConversorAudioController::convertirEnLote()/descargarLote()). No hay tabla
 * en BD para esto, se trackea por antigüedad de los archivos en disco.
 */
class LimpiarLotesConversorAudioHuerfanos extends Command
{
    protected $signature = 'conversor-audio:limpiar-lotes-huerfanos';

    protected $description = 'Elimina las carpetas de lotes de conversión de audio abandonados';

    public function handle(): int
    {
        $this->info('Iniciando limpieza de lotes de conversión de audio huérfanos...');

        $horasExpiracion = config('conversor_audio.lote_expiracion_horas', 2);
        $limite = now()->subHours($horasExpiracion)->timestamp;

        try {
            if (!Storage::disk('local')->exists('conversor_audio_temp')) {
                $this->info('No hay carpetas de lotes temporales.');
                return 0;
            }

            $directoriosUsuarios = Storage::disk('local')->directories('conversor_audio_temp');
            $eliminados = 0;
            $total = 0;

            foreach ($directoriosUsuarios as $dirUsuario) {
                foreach (Storage::disk('local')->directories($dirUsuario) as $dirLote) {
                    $total++;
                    $archivos = Storage::disk('local')->files($dirLote);

                    $masReciente = collect($archivos)
                        ->map(fn ($f) => Storage::disk('local')->lastModified($f))
                        ->max();

                    if ($masReciente === null || $masReciente < $limite) {
                        Storage::disk('local')->deleteDirectory($dirLote);
                        $eliminados++;
                        $this->line("✓ Eliminado: {$dirLote}");
                    }
                }
            }

            $this->info("Limpieza completada: {$eliminados} lote(s) eliminado(s) de {$total}.");

            Log::info('Limpieza de lotes de conversión de audio huérfanos completada', [
                'eliminados' => $eliminados,
                'total' => $total,
            ]);

            return 0;
        } catch (\Exception $e) {
            $this->error("Error: {$e->getMessage()}");

            Log::error('Error en limpieza de lotes de conversión de audio huérfanos', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return 1;
        }
    }
}
