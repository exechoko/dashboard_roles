<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class SeederTablaPermisos extends Seeder
{
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

            //tabla dependencia
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
            'editar-auditoria',
            'borrar-auditoria',

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
            'ver-menu-documentacion',
            'ver-menu-transcripcion',
            'ver-menu-transcripcion-aws',
            'ver-menu-entregas',
            'ver-menu-bodycams',
            'ver-menu-gestor-claves',
            'ver-menu-patrimonio',

            //CeCoCo
            'ver-llamadas-cecoco',
            'ver-moviles-cecoco',
            'buscar-moviles-parados',
            'buscar-moviles-recorridos',
            'ver-eventos-cecoco',
            'ver-mapa-calor-servicios-cecoco',
            'ver-mapa-cecoco-en-vivo',

            //ENTREGAS – Equipos
            'ver-entrega-equipos',
            'crear-entrega-equipos',
            'editar-entrega-equipos',
            'borrar-entrega-equipos',

            //ENTREGAS – Bodycams
            'ver-entrega-bodycams',
            'crear-entrega-bodycams',
            'editar-entrega-bodycams',
            'borrar-entrega-bodycams',

            //Bodycams
            'ver-bodycam',
            'crear-bodycam',
            'editar-bodycam',
            'borrar-bodycam',

            //Gestor de claves
            'ver-clave',
            'crear-clave',
            'editar-clave',
            'borrar-clave',
            'compartir-clave',

            //Patrimonio
            'ver-bien',
            'crear-bien',
            'editar-bien',
            'baja-bien',

            //Tipo Bien
            'ver-tipo-bien',
            'crear-tipo-bien',
            'editar-tipo-bien',
            'borrar-tipo-bien',

            //Acciones varias
            'reiniciar-camara',
            'herramientas-mapa',
        ];

        // Crear permisos si no existen
        foreach ($permisos as $permission) {
            if (!Permission::where('name', $permission)->exists()) {
                Permission::create(['name' => $permission]);
            }
        }
    }
}
