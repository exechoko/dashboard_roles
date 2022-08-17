<?php

namespace Database\Seeders;

use App\Models\Departamental;
use Illuminate\Database\Seeder;

class SeederTablaDepartamentales extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $departamentales = [
            'Colon',
            'Concordia',
            'Diamante',
            'Federación',
            'Federal',
            'Feliciano',
            'Gualeguay',
            'Islas del Ibicuy',
            'La Paz',
            'Nogoya',
            'Paraná',
            'San Salvador',
            'Tala',
            'Uruguay',
            'Victoria',
            'Villaguay',
        ];

        foreach($departamentales as $departamental){
            Departamental::create([
                'direccion_id' => '1',
                'nombre' => $departamental
            ]);
        }
    }
}
