<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Crea los permisos del módulo "Control de Armas -> Armería" (armas
 * secundarias y chalecos) y los asigna a los roles por defecto.
 * Ejecutar: php artisan db:seed --class=SeederPermisosArmeria
 */
class SeederPermisosArmeria extends Seeder
{
    public function run(): void
    {
        $permisos = [
            'ver-armeria',
            'crear-armeria',
            'editar-armeria',
            'borrar-armeria',
        ];

        foreach ($permisos as $nombre) {
            Permission::firstOrCreate(['name' => $nombre, 'guard_name' => 'web']);
        }

        $rolesConAcceso = ['Administrador', 'Super Administrador'];

        foreach ($rolesConAcceso as $nombreRol) {
            $rol = Role::where('name', $nombreRol)->first();
            if ($rol) {
                $rol->givePermissionTo($permisos);
                $this->command->info("Permisos de Armería asignados a: {$nombreRol}");
            } else {
                $this->command->warn("Rol no encontrado: {$nombreRol}");
            }
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('Permisos de Armería creados y asignados correctamente.');
    }
}
