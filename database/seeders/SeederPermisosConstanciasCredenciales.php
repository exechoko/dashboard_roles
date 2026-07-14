<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Crea los permisos del módulo "Actas de Credenciales" y los asigna a los roles
 * por defecto. Ejecutar: php artisan db:seed --class=SeederPermisosConstanciasCredenciales
 */
class SeederPermisosConstanciasCredenciales extends Seeder
{
    public function run(): void
    {
        $permisos = [
            'ver-menu-constancias-credenciales',
            'ver-constancias-credenciales',
            'crear-constancias-credenciales',
            'editar-constancias-credenciales',
            'borrar-constancias-credenciales',
        ];

        foreach ($permisos as $nombre) {
            Permission::firstOrCreate(['name' => $nombre, 'guard_name' => 'web']);
        }

        $rolesConAcceso = ['Administrador', 'Super Administrador'];

        foreach ($rolesConAcceso as $nombreRol) {
            $rol = Role::where('name', $nombreRol)->first();
            if ($rol) {
                $rol->givePermissionTo($permisos);
                $this->command->info("Permisos de Constancias de Credenciales asignados a: {$nombreRol}");
            } else {
                $this->command->warn("Rol no encontrado: {$nombreRol}");
            }
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('Permisos de Constancias de Credenciales creados y asignados correctamente.');
    }
}
