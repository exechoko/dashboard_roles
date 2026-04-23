<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Crea los permisos del módulo Manuales y los asigna a los roles correspondientes.
 * Ejecutar: php artisan db:seed --class=SeederPermisosManuales
 */
class SeederPermisosManuales extends Seeder
{
    public function run(): void
    {
        $permisos = [
            // Menú principal
            'ver-menu-manuales',

            // Subitem: Manual de Usuario (enlace externo)
            'ver-manual-usuario',

            // Subitem: Manuales CeCoCo
            'ver-manuales-cecoco',
            'cargar-manuales-cecoco',
            'descargar-manuales-cecoco',
            'borrar-manuales-cecoco',

            // Subitem: Instructivos
            'ver-instructivos',
            'cargar-instructivos',
            'descargar-instructivos',
            'borrar-instructivos',
        ];

        foreach ($permisos as $nombre) {
            Permission::firstOrCreate(['name' => $nombre, 'guard_name' => 'web']);
        }

        $rolesConAcceso = ['Administrador', 'Super Administrador'];

        foreach ($rolesConAcceso as $nombreRol) {
            $rol = Role::where('name', $nombreRol)->first();
            if ($rol) {
                $rol->givePermissionTo($permisos);
                $this->command->info("Permisos Manuales asignados a: {$nombreRol}");
            } else {
                $this->command->warn("Rol no encontrado: {$nombreRol}");
            }
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('Permisos Manuales creados y asignados correctamente.');
    }
}
