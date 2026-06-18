<?php

namespace App\Console\Commands;

use App\Services\GeneradorDependenciasJs;
use Illuminate\Console\Command;
use Throwable;

class GenerarDependenciasWeb extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'web:generar-dependencias';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenera js/dependencias-data.js de la web pública a partir de las dependencias guardadas en la BD.';

    /**
     * Execute the console command.
     */
    public function handle(GeneradorDependenciasJs $generador): int
    {
        try {
            $ruta = $generador->generar();
        } catch (Throwable $e) {
            $this->error('No se pudo generar el archivo: ' . $e->getMessage());

            return self::FAILURE;
        }

        $this->info("dependencias-data.js regenerado en: {$ruta}");

        return self::SUCCESS;
    }
}
