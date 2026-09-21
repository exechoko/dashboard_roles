<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Crea los permisos del módulo "Personal -> Por Sección" (visualizador de
 * dotación por sección de 911, con anotaciones) y los asigna a los roles
 * por defecto.
 * Ejecutar: php artisan db:seed --class=SeederPermisosPersonalSecciones
 */
class SeederPermisosPersonalSecciones extends Seeder
{
    public function run(): void
    {
        $permisos = ['ver-personal-secciones', 'crear-personal-seccion-nota', 'sincronizar-personal-secciones'];

        foreach ($permisos as $nombre) {
            Permission::firstOrCreate(['name' => $nombre, 'guard_name' => 'web']);
        }

        $rolesConAcceso = ['Administrador', 'Super Administrador'];
        foreach ($rolesConAcceso as $nombreRol) {
            $rol = Role::where('name', $nombreRol)->first();
            if ($rol) {
                $rol->givePermissionTo($permisos);
                $this->command->info("Permisos de Personal por Sección asignados a: {$nombreRol}");
            } else {
                $this->command->warn("Rol no encontrado: {$nombreRol}");
            }
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        $this->command->info('Permisos de Personal por Sección creados y asignados correctamente.');
    }
}
