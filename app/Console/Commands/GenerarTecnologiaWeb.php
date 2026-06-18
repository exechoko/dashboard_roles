<?php

namespace App\Console\Commands;

use App\Services\GeneradorTecnologiaJs;
use Illuminate\Console\Command;
use Throwable;

class GenerarTecnologiaWeb extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'web:generar-tecnologia';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenera js/tecnologia-data.js de la web pública a partir de las cards de tecnología guardadas en la BD.';

    /**
     * Execute the console command.
     */
    public function handle(GeneradorTecnologiaJs $generador): int
    {
        try {
            $ruta = $generador->generar();
        } catch (Throwable $e) {
            $this->error('No se pudo generar el archivo: ' . $e->getMessage());

            return self::FAILURE;
        }

        $this->info("tecnologia-data.js regenerado en: {$ruta}");

        return self::SUCCESS;
    }
}
