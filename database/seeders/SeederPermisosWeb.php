<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Crea los permisos del módulo "Administrar Web" y los asigna a los roles
 * por defecto. Ejecutar: php artisan db:seed --class=SeederPermisosWeb
 */
class SeederPermisosWeb extends Seeder
{
    public function run(): void
    {
        $permisos = [
            'ver-menu-web',
            'editar-web-contadores',
            'editar-web-textos',
            'editar-web-dependencias',
            'crear-noticia',
            'editar-noticia',
            'eliminar-noticia',
        ];

        foreach ($permisos as $nombre) {
            Permission::firstOrCreate(['name' => $nombre, 'guard_name' => 'web']);
        }

        $rolesConAcceso = ['Administrador', 'Super Administrador'];

        foreach ($rolesConAcceso as $nombreRol) {
            $rol = Role::where('name', $nombreRol)->first();
            if ($rol) {
                $rol->givePermissionTo($permisos);
                $this->command->info("Permisos de Web asignados a: {$nombreRol}");
            } else {
                $this->command->warn("Rol no encontrado: {$nombreRol}");
            }
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('Permisos de Web creados y asignados correctamente.');
    }
}
