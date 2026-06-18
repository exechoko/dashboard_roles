<?php

namespace App\Console\Commands;

use App\Services\GeneradorConfigTextos;
use Illuminate\Console\Command;
use Throwable;

class GenerarTextosWeb extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'web:generar-textos';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenera config-textos.js de la web pública a partir de los textos guardados (o los valores por defecto del catálogo).';

    /**
     * Execute the console command.
     */
    public function handle(GeneradorConfigTextos $generador): int
    {
        try {
            $ruta = $generador->generar();
        } catch (Throwable $e) {
            $this->error('No se pudo generar el archivo: ' . $e->getMessage());

            return self::FAILURE;
        }

        $this->info("config-textos.js regenerado en: {$ruta}");

        return self::SUCCESS;
    }
}
