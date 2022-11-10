<?php

namespace Database\Seeders;

use App\Models\TipoAccesorios;
use Illuminate\Database\Seeder;

class SeederTipoAccesorios extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        TipoAccesorios::create([
            'nombre' => 'Cuna cargadora',
            'marca' => 'Teltronic',
        ]);
        TipoAccesorios::create([
            'nombre' => 'Cuna cargadora',
            'marca' => 'Sepura',
        ]);
        TipoAccesorios::create([
            'nombre' => 'Parlante',
        ]);
        TipoAccesorios::create([
            'nombre' => 'PTT',
            'marca' => 'Teltronic',
        ]);
        TipoAccesorios::create([
            'nombre' => 'PTT',
            'marca' => 'Sepura',
        ]);
        TipoAccesorios::create([
            'nombre' => 'GPS',
        ]);
        TipoAccesorios::create([
            'nombre' => 'Kit de instalación',
        ]);
    }
}
