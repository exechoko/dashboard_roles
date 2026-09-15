<?php

namespace Database\Seeders;

use App\Models\Antena;
use Illuminate\Database\Seeder;

/**
 * Carga las antenas (SBS) que antes estaban hardcodeadas en
 * MapaController::antenasFijas(), con la altura de cada mástil.
 * Ejecutar: php artisan db:seed --class=SeederAntenas
 */
class SeederAntenas extends Seeder
{
    public function run(): void
    {
        $antenas = [
            [
                'nombre' => 'SBS 1',
                'localidad' => 'Paraná',
                'latitud' => -31.72652,
                'longitud' => -60.53293,
                'altura' => 50,
                'activa' => true,
            ],
            [
                'nombre' => 'SBS 2',
                'localidad' => 'Paraná',
                'latitud' => -31.75109,
                'longitud' => -60.48563,
                'altura' => 36,
                'activa' => true,
            ],
            [
                'nombre' => 'SBS 3',
                'localidad' => 'Paraná',
                'latitud' => -31.77106,
                'longitud' => -60.52482,
                'altura' => 50,
                'activa' => true,
            ],
            [
                'nombre' => 'SBS 11',
                'localidad' => 'Concordia',
                'latitud' => -31.324043,
                'longitud' => -58.012072,
                'altura' => null,
                'activa' => true,
            ],
            [
                'nombre' => 'SBS 12',
                'localidad' => 'Concordia',
                'latitud' => -31.391542,
                'longitud' => -58.032703,
                'altura' => null,
                'activa' => true,
            ],
        ];

        foreach ($antenas as $antena) {
            Antena::firstOrCreate(
                ['nombre' => $antena['nombre']],
                $antena
            );
        }

        $this->command->info('Antenas (SBS) cargadas correctamente.');
    }
}
