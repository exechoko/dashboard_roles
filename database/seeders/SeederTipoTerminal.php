<?php

namespace Database\Seeders;

use App\Models\TipoTerminal;
use Illuminate\Database\Seeder;

class SeederTipoTerminal extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        TipoTerminal::create([
            'tipo_uso_id' => 1,
            'marca' => 'Teltronic',
            'modelo' => 'HTT500',
        ]);
        TipoTerminal::create([
            'tipo_uso_id' => 1,
            'marca' => 'Sepura',
            'modelo' => 'STP9080',
        ]);
        TipoTerminal::create([
            'tipo_uso_id' => 2,
            'marca' => 'Teltronic',
            'modelo' => 'MDT400',
        ]);
        TipoTerminal::create([
            'tipo_uso_id' => 3,
            'marca' => 'Teltronic',
            'modelo' => 'DT410',
        ]);
        TipoTerminal::create([
            'tipo_uso_id' => 2,
            'marca' => 'Sepura',
            'modelo' => 'SRG3900',
        ]);
    }
}
