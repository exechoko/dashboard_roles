<?php

namespace Tests\Feature;

use App\Models\Notificacion;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NotificacionTest extends TestCase
{
    use DatabaseTransactions;

    public function test_invitado_es_redirigido_a_login(): void
    {
        $this->get(route('notificaciones.sync'))->assertRedirect(route('login'));
    }

    public function test_sync_sin_permiso_es_403(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->getJson(route('notificaciones.sync'))
            ->assertForbidden();
    }

    public function test_sync_devuelve_notificaciones_y_conteo_no_leidas(): void
    {
        $usuario = $this->usuarioConPermiso('ver-infraestructura-notificaciones');

        Notificacion::create([
            'categoria' => Notificacion::CATEGORIA_INFRAESTRUCTURA,
            'tipo' => Notificacion::TIPO_ALERTA,
            'nivel' => 'danger',
            'titulo' => 'Alerta: PC-TEST',
            'mensaje' => 'No responde (10.0.0.99)',
        ]);

        $respuesta = $this->actingAs($usuario)
            ->getJson(route('notificaciones.sync'))
            ->assertOk()
            ->json();

        $this->assertCount(1, $respuesta['notificaciones']);
        $this->assertSame(1, $respuesta['no_leidas_total']);
        $this->assertFalse($respuesta['notificaciones'][0]['leida']);
    }

    public function test_marcar_leidas_actualiza_el_conteo(): void
    {
        $usuario = $this->usuarioConPermiso('ver-infraestructura-notificaciones');

        Notificacion::create([
            'categoria' => Notificacion::CATEGORIA_INFRAESTRUCTURA,
            'tipo' => Notificacion::TIPO_ALERTA,
            'nivel' => 'danger',
            'titulo' => 'Alerta: PC-TEST',
            'mensaje' => 'No responde (10.0.0.99)',
        ]);

        $this->actingAs($usuario)
            ->postJson(route('notificaciones.marcar-leidas'))
            ->assertOk();

        $this->assertNotNull($usuario->fresh()->notificaciones_vistas_en);

        $respuesta = $this->actingAs($usuario)
            ->getJson(route('notificaciones.sync'))
            ->assertOk()
            ->json();

        $this->assertSame(0, $respuesta['no_leidas_total']);
        $this->assertTrue($respuesta['notificaciones'][0]['leida']);
    }

    public function test_vaciar_borra_el_historial(): void
    {
        $usuario = $this->usuarioConPermiso('ver-infraestructura-notificaciones');

        Notificacion::create([
            'categoria' => Notificacion::CATEGORIA_INFRAESTRUCTURA,
            'tipo' => Notificacion::TIPO_ALERTA,
            'nivel' => 'danger',
            'titulo' => 'Alerta: PC-TEST',
            'mensaje' => 'No responde (10.0.0.99)',
        ]);

        $this->actingAs($usuario)
            ->deleteJson(route('notificaciones.vaciar'))
            ->assertOk();

        $this->assertSame(0, Notificacion::count());
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
