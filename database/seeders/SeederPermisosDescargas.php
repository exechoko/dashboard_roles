<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Crea los permisos del módulo "Plataforma de Descargas" y los asigna a los roles
 * por defecto. Ejecutar: php artisan db:seed --class=SeederPermisosDescargas
 */
class SeederPermisosDescargas extends Seeder
{
    public function run(): void
    {
        $permisos = [
            'ver-menu-descargas',
            'ver-plataforma-descargas',
            'subir-archivos-descargas',
            'administrar-plataforma-descargas',
            'ver-logs-descargas',
            'generar-links-publicos',
        ];

        foreach ($permisos as $nombre) {
            Permission::firstOrCreate(['name' => $nombre, 'guard_name' => 'web']);
        }

        $rolesConAccesoCompleto = ['Administrador', 'Super Administrador'];
        $rolesConSoloLectura = [];

        // Asignar todos los permisos a admin y superadmin
        foreach ($rolesConAccesoCompleto as $nombreRol) {
            $rol = Role::where('name', $nombreRol)->first();
            if ($rol) {
                $rol->givePermissionTo($permisos);
                $this->command->info("Permisos de Descargas asignados a: {$nombreRol}");
            } else {
                $this->command->warn("Rol no encontrado: {$nombreRol}");
            }
        }

        // Asignar solo ver-plataforma-descargas a los demás roles si se especifican
        foreach ($rolesConSoloLectura as $nombreRol) {
            $rol = Role::where('name', $nombreRol)->first();
            if ($rol) {
                $rol->givePermissionTo('ver-plataforma-descargas');
                $this->command->info("Permiso de solo lectura de Descargas asignado a: {$nombreRol}");
            }
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('Permisos de Descargas creados y asignados correctamente.');
    }
}
