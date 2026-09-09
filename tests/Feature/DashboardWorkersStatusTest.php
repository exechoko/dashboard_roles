<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardWorkersStatusTest extends TestCase
{
    use DatabaseTransactions;

    // Este endpoint vivía en HomeController (dashboard); se movió a
    // InfraestructuraController (pantalla "Workers y Bases de Datos") y ahora
    // requiere el permiso ver-infraestructura-workers.

    // No se testea el render de '/' (HomeController::index): agrega varias
    // consultas pesadas y llamadas externas (LibreNMS, CECOCO) que la hacen
    // demasiado lenta y frágil para un test — no relacionadas con este cambio,
    // que solo agrega un bloque estático más al mismo layout ya probado.

    public function test_el_endpoint_de_estado_incluye_los_contadores_de_la_cola_mbox(): void
    {
        $admin = $this->usuarioConPermiso('ver-infraestructura-workers');

        $respuesta = $this->actingAs($admin)->getJson(route('api.infraestructura.workers-status'));

        $respuesta->assertOk();
        $respuesta->assertJsonStructure(['mbox_worker_activo', 'mbox_pendientes', 'mbox_procesando']);
    }

    public function test_el_endpoint_de_estado_incluye_los_contadores_de_la_cola_backups(): void
    {
        $admin = $this->usuarioConPermiso('ver-infraestructura-workers');

        $respuesta = $this->actingAs($admin)->getJson(route('api.infraestructura.workers-status'));

        $respuesta->assertOk();
        $respuesta->assertJsonStructure(['backups_worker_activo', 'backups_pendientes', 'backups_procesando']);
    }

    public function test_el_endpoint_de_estado_incluye_los_contadores_de_la_cola_descargas(): void
    {
        $admin = $this->usuarioConPermiso('ver-infraestructura-workers');

        $respuesta = $this->actingAs($admin)->getJson(route('api.infraestructura.workers-status'));

        $respuesta->assertOk();
        $respuesta->assertJsonStructure(['descargas_worker_activo', 'descargas_pendientes', 'descargas_procesando']);
    }

    public function test_sin_el_permiso_de_workers_devuelve_403(): void
    {
        $usuario = User::factory()->create();

        $respuesta = $this->actingAs($usuario)->getJson(route('api.infraestructura.workers-status'));

        $respuesta->assertForbidden();
    }

    private function usuarioConPermiso(string $permiso): User
    {
        $role = Role::firstOrCreate(['name' => 'rol_test_' . $permiso, 'guard_name' => 'web']);
        $role->givePermissionTo(Permission::firstOrCreate(['name' => $permiso, 'guard_name' => 'web']));

        $usuario = User::factory()->create();
        $usuario->assignRole($role);

        return $usuario;
    }
}
