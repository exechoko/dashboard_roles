<?php

namespace Database\Seeders;

use App\Models\ParteDiarioConsigna;
use Illuminate\Database\Seeder;

/**
 * Carga la lista base de consignas de "Asignación de servicios" del parte de motos.
 * Ejecutar: php artisan db:seed --class=SeederParteDiarioConsignas
 */
class SeederParteDiarioConsignas extends Seeder
{
    public function run(): void
    {
        $base = [
            ['grupo' => 'Microcentro', 'nombre' => 'Sector 1'],
            ['grupo' => 'Microcentro', 'nombre' => 'Sector 2'],
            ['grupo' => 'Microcentro', 'nombre' => 'Sector 3'],
            ['grupo' => 'Microcentro', 'nombre' => 'Sector 4'],
            ['grupo' => null, 'nombre' => 'Costanera'],
            ['grupo' => null, 'nombre' => 'Puente Rojo'],
            ['grupo' => null, 'nombre' => 'Puente Blanco'],
            ['grupo' => null, 'nombre' => 'Refuerzo Micro'],
            ['grupo' => null, 'nombre' => 'Cría 1° y 17°'],
            ['grupo' => null, 'nombre' => 'Cría 2° y 8°'],
            ['grupo' => null, 'nombre' => 'Sub Oficial'],
        ];

        foreach ($base as $orden => $consigna) {
            ParteDiarioConsigna::updateOrCreate(
                ['grupo' => $consigna['grupo'], 'nombre' => $consigna['nombre']],
                ['orden' => $orden, 'activa' => true],
            );
        }

        $this->command->info('Consignas base del parte de motos cargadas (' . count($base) . ').');
    }
}
