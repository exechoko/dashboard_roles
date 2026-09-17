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
        // Reemplazados por permisos más granulares (ver comentarios abajo) o
        // sin ningún uso en el código (gestionar-ficha-vehiculo,
        // asignar-vehiculo-a-recurso).
        $obsoletos = [
            'gestionar-flota-911',
            'generar-parte-diario',
            'gestionar-ficha-vehiculo',
            'asignar-vehiculo-a-recurso',
        ];

        foreach ($obsoletos as $nombre) {
            Permission::where('name', $nombre)->where('guard_name', 'web')->delete();
        }

        $permisos = [
            'ver-flota-911',
            'generar-parte-diario-moviles',
            'generar-parte-diario-motos',
            'ver-historial-parte-diario',
            'configurar-parte-diario',
            'generar-estado-flota',
            'ver-prestamos-flota-911',
            'editar-prestamos-flota-911',
            'ver-transferencias-flota-911',
            'reportar-transferencia-recurso',
            'confirmar-transferencia-recurso',
            'editar-estado-flota-911',
            'ver-bitacora-solicitudes-flota-911',
            'registrar-bitacora-flota-911',
            'moderar-bitacora-flota-911',
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
