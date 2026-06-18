<?php

namespace App\Console\Commands;

use App\Services\GeneradorHistoriaJs;
use Illuminate\Console\Command;
use Throwable;

class GenerarHistoriaWeb extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'web:generar-historia';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenera js/historia-data.js de la web pública a partir de las tarjetas de historia guardadas en la BD.';

    /**
     * Execute the console command.
     */
    public function handle(GeneradorHistoriaJs $generador): int
    {
        try {
            $ruta = $generador->generar();
        } catch (Throwable $e) {
            $this->error('No se pudo generar el archivo: ' . $e->getMessage());

            return self::FAILURE;
        }

        $this->info("historia-data.js regenerado en: {$ruta}");

        return self::SUCCESS;
    }
}
