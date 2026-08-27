<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Crea los permisos del módulo "Infraestructura" (PCs, servidores, cámaras
 * internas, routers/switches, LibreNMS, central telefónica, workers/BD) y
 * los asigna a los roles por defecto.
 * Ejecutar: php artisan db:seed --class=SeederPermisosInfraestructura
 */
class SeederPermisosInfraestructura extends Seeder
{
    public function run(): void
    {
        $permisos = [
            'ver-menu-infraestructura',
            'ver-infraestructura-pcs',
            'ver-infraestructura-servidores',
            'ver-infraestructura-camaras',
            'ver-infraestructura-red',
            'ver-infraestructura-librenms',
            'ver-infraestructura-central-telefonica',
            'ver-infraestructura-workers',
            'ver-infraestructura-notificaciones',
            'refrescar-infraestructura',
        ];

        foreach ($permisos as $nombre) {
            Permission::firstOrCreate(['name' => $nombre, 'guard_name' => 'web']);
        }

        $rolesConAcceso = ['Administrador', 'Super Administrador'];

        foreach ($rolesConAcceso as $nombreRol) {
            $rol = Role::where('name', $nombreRol)->first();
            if ($rol) {
                $rol->givePermissionTo($permisos);
                $this->command->info("Permisos de Infraestructura asignados a: {$nombreRol}");
            } else {
                $this->command->warn("Rol no encontrado: {$nombreRol}");
            }
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('Permisos de Infraestructura creados y asignados correctamente.');
    }
}
