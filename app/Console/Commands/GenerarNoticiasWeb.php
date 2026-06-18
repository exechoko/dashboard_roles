<?php

namespace App\Console\Commands;

use App\Services\GeneradorNoticiasJson;
use Illuminate\Console\Command;
use Throwable;

class GenerarNoticiasWeb extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'web:generar-noticias';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenera el archivo noticias.json de la web pública a partir de las noticias publicadas.';

    /**
     * Execute the console command.
     */
    public function handle(GeneradorNoticiasJson $generador): int
    {
        try {
            $ruta = $generador->generar();
        } catch (Throwable $e) {
            $this->error('No se pudo generar el archivo: ' . $e->getMessage());

            return self::FAILURE;
        }

        $this->info("noticias.json regenerado en: {$ruta}");

        return self::SUCCESS;
    }
}
