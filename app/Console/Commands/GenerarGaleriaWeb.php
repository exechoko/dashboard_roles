<?php

namespace App\Console\Commands;

use App\Services\GeneradorGaleriaJs;
use Illuminate\Console\Command;
use Throwable;

class GenerarGaleriaWeb extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'web:generar-galeria';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenera js/galeria-data.js de la web pública a partir de las imágenes de galería guardadas en la BD.';

    /**
     * Execute the console command.
     */
    public function handle(GeneradorGaleriaJs $generador): int
    {
        try {
            $ruta = $generador->generar();
        } catch (Throwable $e) {
            $this->error('No se pudo generar el archivo: ' . $e->getMessage());

            return self::FAILURE;
        }

        $this->info("galeria-data.js regenerado en: {$ruta}");

        return self::SUCCESS;
    }
}
