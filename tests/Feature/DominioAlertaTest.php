<?php

namespace Tests\Feature;

use App\Models\AlertaMovimiento;
use App\Models\DominioAlerta;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DominioAlertaTest extends TestCase
{
    use DatabaseTransactions;

    public function test_un_usuario_no_autenticado_no_puede_acceder(): void
    {
        $response = $this->get(route('alertas-video.dominios.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_un_usuario_autenticado_puede_ver_el_listado(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        DominioAlerta::factory()->create(['dominio' => 'AA123BB']);

        $response = $this->actingAs($admin)->get(route('alertas-video.dominios.index'));

        $response->assertOk();
        $response->assertSee('AA123BB');
    }

    public function test_puede_cargar_un_dominio_y_queda_registrado_en_el_historial(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();

        $response = $this->actingAs($admin)->post(route('alertas-video.dominios.store'), [
            'dominio' => 'zz999zz',
            'marca' => 'Ford',
            'motivo' => 'Robo denunciado',
        ]);

        $dominio = DominioAlerta::where('dominio', 'ZZ999ZZ')->firstOrFail();
        $response->assertRedirect(route('alertas-video.dominios.index'));
        $this->assertTrue($dominio->activo);
        $this->assertSame(1, AlertaMovimiento::where('movable_id', $dominio->id)
            ->where('movable_type', DominioAlerta::class)
            ->where('accion', 'CARGA')
            ->count());
    }

    public function test_el_dominio_se_normaliza_a_mayusculas_sin_espacios_ni_guiones(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();

        $this->actingAs($admin)->post(route('alertas-video.dominios.store'), [
            'dominio' => ' aa-123 bb ',
        ]);

        $this->assertNotNull(DominioAlerta::where('dominio', 'AA123BB')->first());
    }

    public function test_puede_marcar_un_dominio_como_parcial(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();

        $this->actingAs($admin)->post(route('alertas-video.dominios.store'), [
            'dominio' => 'AB123',
            'parcial' => '1',
        ]);

        $dominio = DominioAlerta::where('dominio', 'AB123')->firstOrFail();
        $this->assertTrue($dominio->parcial);
    }

    public function test_el_listado_solo_muestra_activos_por_defecto(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        DominioAlerta::factory()->create(['dominio' => 'ACT111', 'activo' => true]);
        DominioAlerta::factory()->create(['dominio' => 'INA222', 'activo' => false]);

        $response = $this->actingAs($admin)->get(route('alertas-video.dominios.index'));

        $response->assertSee('ACT111');
        $response->assertDontSee('INA222');
    }

    public function test_no_permite_cargar_un_dominio_duplicado(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        DominioAlerta::factory()->create(['dominio' => 'AB111CD']);

        $response = $this->actingAs($admin)->post(route('alertas-video.dominios.store'), [
            'dominio' => 'AB111CD',
        ]);

        $response->assertSessionHasErrors('dominio');
        $this->assertSame(1, DominioAlerta::where('dominio', 'AB111CD')->count());
    }

    public function test_puede_marcar_un_dominio_como_inactivo_y_queda_en_el_historial(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        $dominio = DominioAlerta::factory()->create(['activo' => true]);

        $response = $this->actingAs($admin)->post(route('alertas-video.dominios.activo', $dominio), [
            'activo' => 0,
            'comentario' => 'Vehículo recuperado.',
        ]);

        $response->assertRedirect(route('alertas-video.dominios.show', $dominio));
        $this->assertFalse($dominio->fresh()->activo);
        $this->assertSame(1, AlertaMovimiento::where('movable_id', $dominio->id)
            ->where('movable_type', DominioAlerta::class)
            ->where('accion', 'CAMBIO_ACTIVO')
            ->count());
    }

    public function test_eliminar_requiere_un_motivo(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        $dominio = DominioAlerta::factory()->create();

        $response = $this->actingAs($admin)->delete(route('alertas-video.dominios.destroy', $dominio), [
            'motivo_eliminacion' => 'corto',
        ]);

        $response->assertSessionHasErrors('motivo_eliminacion');
        $this->assertNotNull(DominioAlerta::find($dominio->id));
    }
}
