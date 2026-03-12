<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class LimpiarArchivosTemporales extends Command
{
    protected $signature = 'cecoco:limpiar-temporales {--horas=24 : Archivos más antiguos que X horas}';
    protected $description = 'Limpia archivos temporales de importaciones antiguos';

    public function handle()
    {
        $horas = (int) $this->option('horas');
        $limiteHoras = Carbon::now()->subHours($horas);
        
        $archivos = Storage::disk('local')->files('importaciones_temp');
        $eliminados = 0;
        
        foreach ($archivos as $archivo) {
            $timestamp = Storage::disk('local')->lastModified($archivo);
            $fechaModificacion = Carbon::createFromTimestamp($timestamp);
            
            if ($fechaModificacion->lt($limiteHoras)) {
                Storage::disk('local')->delete($archivo);
                $eliminados++;
                $this->info("Eliminado: {$archivo}");
            }
        }
        
        if ($eliminados === 0) {
            $this->info("No se encontraron archivos temporales antiguos (>{$horas}h)");
        } else {
            $this->info("Total de archivos eliminados: {$eliminados}");
        }
        
        return 0;
    }
}
