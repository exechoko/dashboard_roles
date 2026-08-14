<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Crea los permisos del módulo de Chat interno y los asigna a todos los roles,
 * ya que cualquier usuario puede chatear con cualquier otro.
 * Ejecutar: php artisan db:seed --class=SeederPermisosChat
 */
class SeederPermisosChat extends Seeder
{
    public function run(): void
    {
        $permisos = [
            'ver-menu-chat',
            'ver-chat',
        ];

        foreach ($permisos as $nombre) {
            Permission::firstOrCreate(['name' => $nombre, 'guard_name' => 'web']);
        }

        foreach (Role::all() as $rol) {
            $rol->givePermissionTo($permisos);
            $this->command->info("Permisos de Chat asignados a: {$rol->name}");
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('Permisos de Chat creados y asignados correctamente.');
    }
}
