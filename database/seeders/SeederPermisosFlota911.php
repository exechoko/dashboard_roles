<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Crea los permisos del módulo Control de Vehículos - Flota 911.
 * Ejecutar: php artisan db:seed --class=SeederPermisosFlota911
 */
class SeederPermisosFlota911 extends Seeder
{
    public function run(): void
    {
        $permisos = [
            'ver-flota-911',
            'gestionar-flota-911',
            'generar-parte-diario',
            'generar-estado-flota',
            'gestionar-ficha-vehiculo',
            'asignar-vehiculo-a-recurso',
            'confirmar-transferencia-recurso',
        ];

        foreach ($permisos as $nombre) {
            Permission::firstOrCreate(['name' => $nombre, 'guard_name' => 'web']);
        }

        $rolesConAcceso = ['Administrador', 'Super Administrador'];

        foreach ($rolesConAcceso as $nombreRol) {
            $rol = Role::where('name', $nombreRol)->first();
            if ($rol) {
                $rol->givePermissionTo($permisos);
                $this->command->info("Permisos de Flota 911 asignados a: {$nombreRol}");
            } else {
                $this->command->warn("Rol no encontrado: {$nombreRol}");
            }
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('Permisos de Flota 911 creados y asignados correctamente.');
    }
}
