<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class SeederTablaPermisos extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $permisos = [
            //tabla roles
            'ver-rol',
            'crear-rol',
            'editar-rol',
            'borrar-rol',

            //tabla equipos
            'ver-equipo',
            'crear-equipo',
            'editar-equipo',
            'borrar-equipo',

            //tabla usuarios
            'ver-usuario',
            'crear-usuario',
            'editar-usuario',
            'borrar-usuario',

            //tabla terminales
            'ver-terminal',
            'crear-terminal',
            'editar-terminal',
            'borrar-terminal',

            //tabla destino
            'ver-dependencia',
            'crear-dependencia',
            'editar-dependencia',
            'borrar-dependencia',

            //tabla recursos
            'ver-recursos',
            'crear-recursos',
            'editar-recursos',
            'borrar-recursos',

            //tabla historicos
            'ver-historicos',
            'crear-historicos',
            'editar-historicos',
            'borrar-historicos',
        ];

        foreach($permisos as $permiso){
            Permission::create(['name' => $permiso]);
        }
    }
}
