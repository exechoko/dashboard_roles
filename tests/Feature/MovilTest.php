<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class MovilTest extends TestCase
{
    use DatabaseTransactions;

    public function test_un_invitado_es_redirigido_al_login_movil(): void
    {
        $response = $this->get(route('movil.index'));

        $response->assertRedirect(route('movil.login'));
    }

    public function test_un_invitado_puede_loguearse_desde_movil_ingresar_y_vuelve_a_la_pagina_que_queria_ver(): void
    {
        $usuario = $this->usuarioCon(['ver-flota']);

        // Intenta entrar a una página protegida sin sesión: guarda la URL
        // "intended" y redirige al login móvil (no al de escritorio).
        $this->get(route('movil.flota.index'))->assertRedirect(route('movil.login'));

        $response = $this->post(route('movil.login'), [
            'email' => $usuario->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('movil.flota.index'));
        $this->assertAuthenticatedAs($usuario);
    }

    public function test_un_usuario_con_permisos_ve_las_secciones_habilitadas(): void
    {
        $usuario = $this->usuarioCon(['ver-flota', 'ver-camara', 'ver-analizador-eventos-cecoco']);

        $this->actingAs($usuario)->get(route('movil.index'))->assertOk();
        $this->actingAs($usuario)->get(route('movil.flota.index'))->assertOk()->assertViewIs('movil.flota.index');
        $this->actingAs($usuario)->get(route('movil.camaras.index'))->assertOk()->assertViewIs('movil.camaras.index');
        $this->actingAs($usuario)->get(route('movil.mapa.index'))->assertOk()->assertViewIs('movil.mapa.index');
        $this->actingAs($usuario)->get(route('movil.eventos.index'))->assertOk()->assertViewIs('movil.eventos.index');
    }

    public function test_un_usuario_sin_ver_flota_recibe_403_en_flota(): void
    {
        $usuario = $this->usuarioCon(['ver-camara']);

        $this->actingAs($usuario)->get(route('movil.flota.index'))->assertForbidden();
    }

    public function test_un_usuario_sin_ver_camara_recibe_403_en_camaras_y_mapa(): void
    {
        $usuario = $this->usuarioCon(['ver-flota']);

        $this->actingAs($usuario)->get(route('movil.camaras.index'))->assertForbidden();
        $this->actingAs($usuario)->get(route('movil.mapa.index'))->assertForbidden();
    }

    public function test_un_usuario_sin_permiso_recibe_403_en_eventos(): void
    {
        $usuario = $this->usuarioCon(['ver-flota']);

        $this->actingAs($usuario)->get(route('movil.eventos.index'))->assertForbidden();
    }

    public function test_camaras_json_devuelve_una_feature_collection(): void
    {
        $usuario = $this->usuarioCon(['ver-camara']);

        $response = $this->actingAs($usuario)->get(route('movil.mapa.camaras-json'));

        $response->assertOk()->assertJsonStructure(['type', 'features']);
        $this->assertSame('FeatureCollection', $response->json('type'));
    }

    private function usuarioCon(array $permisos): User
    {
        $usuario = User::factory()->create();

        foreach ($permisos as $permiso) {
            $usuario->givePermissionTo(Permission::findOrCreate($permiso, 'web'));
        }

        return $usuario->fresh();
    }
}
