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

            //tabla recurso
            'ver-recurso',
            'crear-recurso',
            'editar-recurso',
            'borrar-recurso',

            //tabla vehiculo
            'ver-vehiculo',
            'crear-vehiculo',
            'editar-vehiculo',
            'borrar-vehiculo',

            //tabla historico
            'ver-historico',
            'crear-historico',
            'editar-historico',
            'borrar-historico',

            //tabla flota
            'ver-flota',
            'crear-flota',
            'editar-flota',
            'borrar-flota',

            //tabla camara
            'ver-camara',
            'crear-camara',
            'editar-camara',
            'borrar-camara',

            //tabla tipo camara
            'ver-tipo-camara',
            'crear-tipo-camara',
            'editar-tipo-camara',
            'borrar-tipo-camara',

            //tabla auditoria
            'ver-auditoria',

            //tabla sitio
            'ver-sitio',
            'crear-sitio',
            'editar-sitio',
            'borrar-sitio',

            //Menu Izquierdo
            'ver-menu-dashboard',
            'ver-menu-equipamientos',
            'ver-menu-camaras',
            'ver-menu-dependencias',
            'ver-menu-mapa',
            'ver-menu-usuarios',
            'ver-menu-auditoria',
            'ver-menu-cecoco',

            //CeCoCo
            'ver-llamadas-cecoco',
            'ver-moviles-cecoco',
            'buscar-moviles-parados',
            'buscar-moviles-recorridos',
            'ver-eventos-cecoco',
        ];

        // Looping and Inserting Array's Permissions into Permission Table
        foreach ($permisos as $permission) {
            // Verificar si el permiso ya existe antes de crearlo
            if (!Permission::where('name', $permission)->exists()) {
                Permission::create(['name' => $permission]);
            }
        }
    }
}
