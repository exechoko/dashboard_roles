<?php

namespace Database\Seeders;

use App\Models\ArmaTipo;
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
            'ver-arma-tipo',
            'crear-arma-tipo',
            'editar-arma-tipo',
            'borrar-arma-tipo',
            'ver-personal',
            'crear-personal',
            'editar-personal',
            'borrar-personal',
            'restaurar-personal',
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

        // Seed 12 weapon types
        $tipos = [
            'BERSA THUNDER',
            'BROWNING',
            'FM HI-POWER',
            'FM HI-POWER DETECTIVE',
            'FM HI-POWER M.90',
            'FM HI-POWER M.95 CLASSIC',
            'GLOCK M.17',
            'GLOCK M.19',
            'BALLESTER MOLINA',
            'RUBÍ EXTRA',
            'SISTEMA COLT',
            'TAURUS',
        ];

        foreach ($tipos as $nombre) {
            ArmaTipo::firstOrCreate(['nombre' => $nombre]);
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('Permisos de Control de Armas creados y asignados correctamente.');
        $this->command->info('Tipos de arma creados correctamente.');
    }
}
