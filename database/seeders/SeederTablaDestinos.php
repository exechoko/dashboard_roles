<?php

namespace Database\Seeders;

use App\Models\Comisaria;
use App\Models\Departamental;
use App\Models\Destino;
use App\Models\Direccion;
use App\Models\Division;
use Illuminate\Database\Seeder;

class SeederTablaDestinos extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $direcciones = Direccion::all();
        $departamentales = Departamental::all();
        $divisiones = Division::all();
        $comisarias = Comisaria::all();

        foreach($direcciones as $direccion){
            Destino::create([
                'direccion_id' => $direccion->id,
                'nombre' => $direccion->nombre,
            ]);
        }

        foreach($departamentales as $departamental){
            Destino::create([
                'departamental_id' => $departamental->id,
                'nombre' => $departamental->nombre,
            ]);
        }
        foreach($divisiones as $division){
            Destino::create([
                'direccion_id' => $division->direccion_id,
                'departamental_id' => $division->departamental_id,
                'division_id' => $division->id,
                'nombre' => $division->nombre,
            ]);
        }
        foreach($comisarias as $comisaria){
            Destino::create([
                'departamental_id' => $comisaria->departamental_id,
                'comisaria_id' =>$comisaria->id,
                'nombre' => $comisaria->nombre,
            ]);
        }
    }
}
