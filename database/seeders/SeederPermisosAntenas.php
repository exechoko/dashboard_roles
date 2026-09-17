<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Crea los permisos del módulo "Equipamientos -> Antenas (SBS)" y los
 * asigna a los roles por defecto.
 * Ejecutar: php artisan db:seed --class=SeederPermisosAntenas
 */
class SeederPermisosAntenas extends Seeder
{
    public function run(): void
    {
        $permisos = [
            'ver-antena',
            'crear-antena',
            'editar-antena',
            'borrar-antena',
        ];

        foreach ($permisos as $nombre) {
            Permission::firstOrCreate(['name' => $nombre, 'guard_name' => 'web']);
        }

        $rolesConAcceso = ['Administrador', 'Super Administrador'];

        foreach ($rolesConAcceso as $nombreRol) {
            $rol = Role::where('name', $nombreRol)->first();
            if ($rol) {
                $rol->givePermissionTo($permisos);
                $this->command->info("Permisos de Antenas asignados a: {$nombreRol}");
            } else {
                $this->command->warn("Rol no encontrado: {$nombreRol}");
            }
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('Permisos de Antenas creados y asignados correctamente.');
    }
}
