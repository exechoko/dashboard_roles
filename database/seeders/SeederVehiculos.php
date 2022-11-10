<?php

namespace Database\Seeders;

use App\Models\Vehiculo;
use Illuminate\Database\Seeder;

class SeederVehiculos extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //$tipo_vehiculo = ['Auto', 'Camioneta', 'Camión', 'Moto', 'Helicoptero'];
        Vehiculo::create([
            'tipo_vehiculo' => 'Auto',
            'marca' => 'Citroen',
            'modelo' => 'C4',
            'dominio' => 'NPR599',
            'color' => 'Identificable',
            'propiedad' => 'Policía de Entre Ríos',
        ]);

        Vehiculo::create([
            'tipo_vehiculo' => 'Camioneta',
            'marca' => 'Chevrolet',
            'modelo' => 'S10',
            'dominio' => 'OZL202',
            'color' => 'Identificable',
            'propiedad' => 'Policía de Entre Ríos',
        ]);

        Vehiculo::create([
            'tipo_vehiculo' => 'Camioneta',
            'marca' => 'Chevrolet',
            'modelo' => 'S10',
            'dominio' => 'OZL231',
            'color' => 'Identificable',
            'propiedad' => 'Policía de Entre Ríos',
        ]);

        Vehiculo::create([
            'tipo_vehiculo' => 'Camioneta',
            'marca' => 'Chevrolet',
            'modelo' => 'S10',
            'dominio' => 'AE202RD',
            'color' => 'Identificable',
            'propiedad' => 'Policía de Entre Ríos',
        ]);

        Vehiculo::create([
            'tipo_vehiculo' => 'Auto',
            'marca' => 'Ford',
            'modelo' => 'Focus',
            'dominio' => 'PCT415',
            'color' => 'Identificable',
            'propiedad' => 'Policía de Entre Ríos',
        ]);

        Vehiculo::create([
            'tipo_vehiculo' => 'Auto',
            'marca' => 'Ford',
            'modelo' => 'Focus',
            'dominio' => 'PCT430',
            'color' => 'Identificable',
            'propiedad' => 'Policía de Entre Ríos',
        ]);
    }
}
