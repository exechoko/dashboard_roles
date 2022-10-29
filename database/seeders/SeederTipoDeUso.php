<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TipoUso;

class SeederTipoDeUso extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $tipos = [
            'Portatil',
            'Movil',
            'Base',
            'Base - Movil',
        ];

        foreach($tipos as $tipo){
            TipoUso::create(['uso' => $tipo]);
        }
    }
}
