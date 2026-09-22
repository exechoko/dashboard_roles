<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Crea los permisos del módulo "Personal -> Por Sección" (visualizador de
 * dotación por sección de 911, con anotaciones) y los asigna a los roles
 * por defecto. También separa la visibilidad del submenú "Armería" de
 * `ver-menu-personal` (antes hacía las dos cosas a la vez) en su propio
 * permiso `ver-menu-armeria`, migrando a quien ya tenía acceso para no
 * romperle el link del menú.
 * Ejecutar: php artisan db:seed --class=SeederPermisosPersonalSecciones
 */
class SeederPermisosPersonalSecciones extends Seeder
{
    public function run(): void
    {
        $permisos = [
            'ver-personal-secciones',
            'crear-personal-seccion-nota',
            'sincronizar-personal-secciones',
            'ver-menu-personal-secciones',
            'ver-menu-armeria',
        ];

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

        // Migración: antes `ver-menu-personal` gateaba a la vez el dropdown
        // padre Y el link de Armería. Ahora Armería tiene su propio permiso
        // (`ver-menu-armeria`), así que cualquier rol que ya tuviera acceso
        // vía el viejo permiso lo mantiene, sin que se le desaparezca el link.
        // Si `ver-menu-personal` todavía no existe en este entorno, no hay
        // nada que migrar.
        if (Permission::where('name', 'ver-menu-personal')->where('guard_name', 'web')->exists()) {
            $rolesConMenuPersonal = Role::permission('ver-menu-personal')->get();
            foreach ($rolesConMenuPersonal as $rol) {
                $rol->givePermissionTo('ver-menu-armeria');
                $this->command->info("Migración: ver-menu-armeria asignado a '{$rol->name}' (ya tenía ver-menu-personal).");
            }
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        $this->command->info('Permisos de Personal por Sección creados y asignados correctamente.');
    }
}
