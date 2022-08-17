<?php

namespace Database\Seeders;

use App\Models\Comisaria;
use Illuminate\Database\Seeder;

class SeederTablaComisarias extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $comisarias = [
            'Primera',
            'Segunda',
            'Tercera',
            'Cuarta',
            'Quinta',
            'Sexta',
            'Septima',
            'Octava',
            'Novena',
            'Décima',
            'Undécima',
            'Duodécima',
            'Décimo tercera',
            'Décimo cuarta',
            'Décimo quinta',
            'Décimo sexta',
            'Décimo septima',
            'San Benito',
            'Colonia Avellaneda',
            'Oro Verde',
        ];

        foreach($comisarias as $comisaria){
            Comisaria::create([
                'departamental_id' => '11',
                'nombre' => $comisaria
            ]);
        }
    }
}
