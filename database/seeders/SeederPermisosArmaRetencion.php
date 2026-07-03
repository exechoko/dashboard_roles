<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Crea los permisos del módulo "Control de Armas" y los asigna a los roles
 * por defecto. Ejecutar: php artisan db:seed --class=SeederPermisosArmaRetencion
 */
class SeederPermisosArmaRetencion extends Seeder
{
    public function run(): void
    {
        $permisos = [
            'ver-menu-armamento',
            'ver-arma-retencion',
            'crear-arma-retencion',
            'editar-arma-retencion',
            'borrar-arma-retencion',
            'ver-arma-motivo',
            'crear-arma-motivo',
            'editar-arma-motivo',
            'borrar-arma-motivo',
            'ver-personal',
            'crear-personal',
            'editar-personal',
            'borrar-personal',
        ];

        foreach ($permisos as $nombre) {
            Permission::firstOrCreate(['name' => $nombre, 'guard_name' => 'web']);
        }

        $rolesConAcceso = ['Administrador', 'Super Administrador'];

        foreach ($rolesConAcceso as $nombreRol) {
            $rol = Role::where('name', $nombreRol)->first();
            if ($rol) {
                $rol->givePermissionTo($permisos);
                $this->command->info("Permisos de Control de Armas asignados a: {$nombreRol}");
            } else {
                $this->command->warn("Rol no encontrado: {$nombreRol}");
            }
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('Permisos de Control de Armas creados y asignados correctamente.');
    }
}
