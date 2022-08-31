<?php

namespace Database\Seeders;

use App\Models\Direccion;
use Illuminate\Database\Seeder;

class SeederTablaDirecciones extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $direcciones = [
            'Dirección Operaciones y Seguridad',
            'Dirección Ayudantía General',
            'Dirección Institutos Policiales',
            'Dirección Logística',
            'Dirección Personal',
            'Dirección Criminalística',
            'Dirección Inteligencia',
            'Dirección Prevención y Seguridad Vial',
            'Dirección Asuntos Internos',
            'Dirección Investigaciones',
            'Dirección Inteligencia Criminal',
            'Dirección Toxicología',
            'Dirección Prevención Delitos Rurales',
        ];

        foreach($direcciones as $direccion){
            Direccion::create(['nombre' => $direccion]);
        }
    }
}
