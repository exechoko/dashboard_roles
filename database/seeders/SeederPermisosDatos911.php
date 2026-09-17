<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Crea el permiso de la pantalla "Datos del 911" (PWA) y lo asigna a los
 * roles por defecto. Cada grupo de la pantalla ademas respeta el permiso
 * puntual del dato que muestra (ver-camara, ver-personal, ver-equipo,
 * ver-antena, ver-vehiculo, ver-reporte-llamadas-central-telefonica,
 * ver-analitica-eventos-cecoco).
 * Ejecutar: php artisan db:seed --class=SeederPermisosDatos911
 */
class SeederPermisosDatos911 extends Seeder
{
    public function run(): void
    {
        $permisos = [
            'ver-datos-911',
        ];

        foreach ($permisos as $nombre) {
            Permission::firstOrCreate(['name' => $nombre, 'guard_name' => 'web']);
        }

        $rolesConAcceso = ['Administrador', 'Super Administrador'];

        foreach ($rolesConAcceso as $nombreRol) {
            $rol = Role::where('name', $nombreRol)->first();
            if ($rol) {
                $rol->givePermissionTo($permisos);
                $this->command->info("Permiso de Datos del 911 asignado a: {$nombreRol}");
            } else {
                $this->command->warn("Rol no encontrado: {$nombreRol}");
            }
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('Permiso de Datos del 911 creado y asignado correctamente.');
    }
}
