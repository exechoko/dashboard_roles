<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Borra las carpetas de chunks_temp/{upload_id} que quedaron abandonadas
 * (el usuario cerro la pestaña o se corto la conexion a mitad de una
 * subida por partes, ver DescargaAdminController::subirChunk()). No hay
 * tabla en BD para esto - se trackea unicamente por antiguedad de los
 * archivos en el disco 'descargas'.
 */
class LimpiarChunksHuerfanos extends Command
{
    protected $signature = 'descargas:limpiar-chunks-huerfanos';

    protected $description = 'Elimina las carpetas de chunks temporales de subidas por partes abandonadas';

    public function handle(): int
    {
        $this->info('Iniciando limpieza de chunks huérfanos...');

        $horasExpiracion = config('descargas.chunks_temp_expiracion_horas', 6);
        $limite = now()->subHours($horasExpiracion)->timestamp;

        try {
            $directorios = Storage::disk('descargas')->directories('chunks_temp');

            if (empty($directorios)) {
                $this->info('No hay carpetas de chunks temporales.');
                return 0;
            }

            $eliminados = 0;

            foreach ($directorios as $dir) {
                $archivos = Storage::disk('descargas')->files($dir);

                // Carpeta vacia, o todos sus archivos son mas viejos que el limite
                $masReciente = collect($archivos)
                    ->map(fn ($f) => Storage::disk('descargas')->lastModified($f))
                    ->max();

                if ($masReciente === null || $masReciente < $limite) {
                    Storage::disk('descargas')->deleteDirectory($dir);
                    $eliminados++;
                    $this->line("✓ Eliminada: {$dir}");
                }
            }

            $this->info("Limpieza completada: {$eliminados} carpeta(s) eliminada(s) de " . count($directorios) . '.');

            Log::info('Limpieza de chunks huérfanos completada', [
                'eliminados' => $eliminados,
                'total' => count($directorios),
            ]);

            return 0;
        } catch (\Exception $e) {
            $this->error("Error: {$e->getMessage()}");

            Log::error('Error en limpieza de chunks huérfanos', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return 1;
        }
    }
}
