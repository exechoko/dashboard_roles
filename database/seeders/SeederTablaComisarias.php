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
            'Comisaría Primera',
            'Comisaría Segunda',
            'Comisaría Tercera',
            'Comisaría Cuarta',
            'Comisaría Quinta',
            'Comisaría Sexta',
            'Comisaría Septima',
            'Comisaría Octava',
            'Comisaría Novena',
            'Comisaría Décima',
            'Comisaría Undécima',
            'Comisaría Duodécima',
            'Comisaría Décimo tercera',
            'Comisaría Décimo cuarta',
            'Comisaría Décimo quinta',
            'Comisaría Décimo sexta',
            'Comisaría Décimo septima',
            'Comisaría San Benito',
            'Comisaría Colonia Avellaneda',
            'Comisaría Oro Verde',
        ];

        foreach($comisarias as $comisaria){
            Comisaria::create([
                'departamental_id' => '12',
                'nombre' => $comisaria
            ]);
        }
    }
}
