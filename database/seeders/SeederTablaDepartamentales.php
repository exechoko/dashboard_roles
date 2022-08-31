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
            'Departamental Colon',
            'Departamental Concordia',
            'Departamental Diamante',
            'Departamental Federación',
            'Departamental Federal',
            'Departamental Feliciano',
            'Departamental Gualeguay',
            'Departamental Gualeguaychu',
            'Departamental Islas del Ibicuy',
            'Departamental La Paz',
            'Departamental Nogoya',
            'Departamental Paraná',
            'Departamental San Salvador',
            'Departamental Tala',
            'Departamental Uruguay',
            'Departamental Victoria',
            'Departamental Villaguay',
        ];

        foreach($departamentales as $departamental){
            Departamental::create([
                'nombre' => $departamental
            ]);
        }
    }
}
