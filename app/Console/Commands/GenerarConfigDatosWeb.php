<?php

namespace App\Console\Commands;

use App\Models\WebConfigDato;
use App\Services\GeneradorConfigDatos;
use Illuminate\Console\Command;
use Throwable;

class GenerarConfigDatosWeb extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'web:generar-config-datos';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenera config-datos.js de la web pública a partir de los valores guardados en la BD.';

    /**
     * Execute the console command.
     */
    public function handle(GeneradorConfigDatos $generador): int
    {
        $datos = WebConfigDato::comoMapa();

        if (empty($datos)) {
            $this->error('No hay datos en web_config_datos. Ejecutá primero: php artisan db:seed --class=WebConfigDatoSeeder');

            return self::FAILURE;
        }

        try {
            $ruta = $generador->generar($datos);
        } catch (Throwable $e) {
            $this->error('No se pudo generar el archivo: ' . $e->getMessage());

            return self::FAILURE;
        }

        $this->info("config-datos.js regenerado en: {$ruta}");

        return self::SUCCESS;
    }
}
