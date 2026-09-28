<?php

namespace Database\Seeders;

use App\Models\PersonalSeccion;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Crea un permiso `ver-seccion-{slug}` por cada sección real que aparece hoy
 * en `personal_secciones` (ej. `ver-seccion-violencia-de-genero`), para poder
 * limitar el módulo "Personal -> Por Sección" a un subconjunto de secciones
 * por rol (ej. un rol "Operador Violencia de Género" que solo vea Género y
 * Judiciales, en vez de la dotación completa de la División).
 *
 * Migración: antes `ver-personal-secciones` alcanzaba para ver TODO. Ahora
 * además hace falta al menos un `ver-seccion-*` para ver algo (ver
 * `PersonalSeccionController::seccionesPermitidasPara()`). Solo Administrador
 * y Super Administrador reciben automáticamente TODAS las secciones (mismo
 * criterio que `SeederPermisosPersonalSecciones`); cualquier otro rol que ya
 * tuviera `ver-personal-secciones` queda SIN ninguna sección asignada —
 * conserva el acceso a la pantalla pero ve la lista vacía hasta que un
 * administrador le tilde manualmente qué secciones puede ver desde la
 * edición de roles.
 *
 * Ejecutar: php artisan db:seed --class=SeederPermisosVisibilidadSecciones
 * (setup inicial / instalación nueva). Para el día a día YA NO hace falta
 * correrlo a mano: `PersonalSeccionSyncService::asegurarPermisosDeSeccion()`
 * hace exactamente esto mismo automáticamente en cada sincronización (diaria
 * 05:30 o botón manual), con el mismo criterio de Administrador/Super
 * Administrador. Este seeder queda como respaldo idempotente, no como paso
 * obligatorio.
 */
class SeederPermisosVisibilidadSecciones extends Seeder
{
    private const ROLES_CON_ACCESO_TOTAL = ['Administrador', 'Super Administrador'];

    public function run(): void
    {
        $secciones = PersonalSeccion::query()
            ->whereNotNull('seccion')
            ->distinct()
            ->pluck('seccion');

        $nombresPermisos = $secciones
            ->map(fn (string $seccion) => PersonalSeccion::permisoVisibilidad($seccion))
            ->unique()
            ->values();

        foreach ($nombresPermisos as $nombre) {
            Permission::firstOrCreate(['name' => $nombre, 'guard_name' => 'web']);
        }

        foreach (self::ROLES_CON_ACCESO_TOTAL as $nombreRol) {
            $rol = Role::where('name', $nombreRol)->first();
            if ($rol) {
                $rol->givePermissionTo($nombresPermisos->all());
                $this->command->info("Todas las secciones asignadas a: {$rol->name}");
            } else {
                $this->command->warn("Rol no encontrado: {$nombreRol}");
            }
        }

        $otrosRolesConAcceso = Role::permission('ver-personal-secciones')->get()
            ->reject(fn (Role $rol) => in_array($rol->name, self::ROLES_CON_ACCESO_TOTAL, true));
        foreach ($otrosRolesConAcceso as $rol) {
            $this->command->warn("'{$rol->name}' tiene 'ver-personal-secciones' pero ninguna sección tildada: va a ver la lista vacía hasta que se le asignen secciones manualmente.");
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        $this->command->info('Permisos de visibilidad por sección creados y asignados correctamente.');
    }
}
