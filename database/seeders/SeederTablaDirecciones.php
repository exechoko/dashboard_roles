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
            'Operaciones y Seguridad',
            'Ayudantía General',
            'Institutos Policiales',
            'Logística',
            'Personal',
            'Criminalística',
            'Inteligencia',
            'Prevención y Seguridad Vial',
            'Asuntos Internos',
            'Investigaciones',
            'Inteligencia Criminal',
            'Toxicología',
            'Prevención Delitos Rurales',
        ];

        foreach($direcciones as $direccion){
            Direccion::create(['nombre' => $direccion]);
        }
    }
}
