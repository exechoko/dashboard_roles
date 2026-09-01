<?php

namespace App\Console\Commands;

use App\Models\DescargaZipTemporal;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class LimpiarZipsExpirados extends Command
{
    protected $signature = 'descargas:limpiar-zips';

    protected $description = 'Elimina los archivos ZIP temporales que han expirado';

    public function handle()
    {
        $this->info('Iniciando limpieza de ZIPs expirados...');

        try {
            // Obtener ZIPs expirados
            $zipsExpirados = DescargaZipTemporal::expirados()->get();

            if ($zipsExpirados->isEmpty()) {
                $this->info('No hay ZIPs expirados para eliminar.');
                return 0;
            }

            $this->info("Se encontraron {$zipsExpirados->count()} ZIPs expirados.");

            $eliminados = 0;
            $errores = 0;

            foreach ($zipsExpirados as $zip) {
                try {
                    // Eliminar archivo físico
                    if (Storage::exists($zip->ruta_zip)) {
                        Storage::delete($zip->ruta_zip);
                    }

                    // Eliminar registro de BD
                    $zip->delete();

                    $eliminados++;

                    $this->line("✓ ZIP eliminado: {$zip->token}");

                } catch (\Exception $e) {
                    $errores++;
                    $this->error("✗ Error eliminando ZIP {$zip->token}: {$e->getMessage()}");

                    Log::error('Error eliminando ZIP expirado', [
                        'zip_id' => $zip->id,
                        'token' => $zip->token,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $this->info('');
            $this->info("Limpieza completada:");
            $this->info("  - Eliminados: {$eliminados}");
            $this->info("  - Errores: {$errores}");

            Log::info('Limpieza de ZIPs expirados completada', [
                'eliminados' => $eliminados,
                'errores' => $errores,
            ]);

            return $errores > 0 ? 1 : 0;

        } catch (\Exception $e) {
            $this->error("Error fatal: {$e->getMessage()}");

            Log::error('Error fatal en limpieza de ZIPs', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return 1;
        }
    }
}
