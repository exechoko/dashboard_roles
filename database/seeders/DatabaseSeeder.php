<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory(10)->create();
        DB::table('direcciones')->truncate();
        DB::table('departamentales')->truncate();
        DB::table('divisiones')->truncate();
        DB::table('comisarias')->truncate();
        DB::table('destino')->truncate();
        DB::table('tipo_uso')->truncate();
        $this->call(SeederTablaDirecciones::class);
        $this->call(SeederTablaDepartamentales::class);
        $this->call(SeederTablaDivisiones::class);
        $this->call(SeederTablaComisarias::class);
        $this->call(SeederTablaDestinos::class);
        $this->call(SeederTipoDeUso::class);
        //$this->call(SeederVehiculos::class);
        //$this->call(SeederTipoVehiculos::class);
    }
}
