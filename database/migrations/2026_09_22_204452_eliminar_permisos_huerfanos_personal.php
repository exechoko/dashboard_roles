<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;

/**
 * `crear-personal`, `borrar-personal` y `restaurar-personal` quedaron sin uso
 * (ningún @can/middleware los consulta) tras sacar de Armería el alta, la baja
 * y la restauración manual de Personal: ese circuito ya lo maneja el sync
 * diario contra personal911. `editar-personal` sigue vigente, acotado a
 * corregir arma/chaleco.
 */
return new class extends Migration
{
    private const PERMISOS_A_ELIMINAR = [
        'crear-personal',
        'borrar-personal',
        'restaurar-personal',
    ];

    public function up(): void
    {
        Permission::whereIn('name', self::PERMISOS_A_ELIMINAR)
            ->where('guard_name', 'web')
            ->get()
            ->each(fn (Permission $permiso) => $permiso->delete());

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        foreach (self::PERMISOS_A_ELIMINAR as $nombre) {
            Permission::firstOrCreate(['name' => $nombre, 'guard_name' => 'web']);
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
