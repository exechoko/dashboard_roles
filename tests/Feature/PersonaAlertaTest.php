<?php

namespace Tests\Feature;

use App\Models\AlertaMovimiento;
use App\Models\PersonaAlerta;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PersonaAlertaTest extends TestCase
{
    use DatabaseTransactions;

    public function test_un_usuario_no_autenticado_no_puede_acceder(): void
    {
        $response = $this->get(route('alertas-video.personas.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_un_usuario_autenticado_puede_ver_el_listado(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        PersonaAlerta::factory()->create(['apellido_nombre' => 'Perez Juan']);

        $response = $this->actingAs($admin)->get(route('alertas-video.personas.index'));

        $response->assertOk();
        $response->assertSee('Perez Juan');
    }

    public function test_puede_cargar_una_persona_con_foto_y_queda_registrada_en_el_historial(): void
    {
        Storage::fake('anexos');
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();

        $response = $this->actingAs($admin)->post(route('alertas-video.personas.store'), [
            'apellido_nombre' => 'Gomez Maria',
            'dni' => '30111222',
            'identificado' => '1',
            'foto' => UploadedFile::fake()->image('rostro.jpg'),
        ]);

        $persona = PersonaAlerta::where('dni', '30111222')->firstOrFail();
        $response->assertRedirect(route('alertas-video.personas.index'));
        $this->assertTrue($persona->identificado);
        $this->assertNotNull($persona->foto);
        Storage::disk('anexos')->assertExists($persona->foto);
        $this->assertSame(1, AlertaMovimiento::where('movable_id', $persona->id)
            ->where('movable_type', PersonaAlerta::class)
            ->where('accion', 'CARGA')
            ->count());
    }

    public function test_no_permite_cargar_una_persona_con_dni_duplicado(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        PersonaAlerta::factory()->create(['dni' => '20333444']);

        $response = $this->actingAs($admin)->post(route('alertas-video.personas.store'), [
            'apellido_nombre' => 'Otro Nombre',
            'dni' => '20333444',
        ]);

        $response->assertSessionHasErrors('dni');
        $this->assertSame(1, PersonaAlerta::where('dni', '20333444')->count());
    }

    public function test_el_dni_se_normaliza_sin_puntos(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();

        $this->actingAs($admin)->post(route('alertas-video.personas.store'), [
            'apellido_nombre' => 'Con Puntos',
            'dni' => '30.111.222',
        ]);

        $this->assertNotNull(PersonaAlerta::where('dni', '30111222')->first());
    }

    public function test_detecta_duplicado_de_dni_aunque_uno_tenga_puntos(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        PersonaAlerta::factory()->create(['dni' => '25444555']);

        $response = $this->actingAs($admin)->post(route('alertas-video.personas.store'), [
            'apellido_nombre' => 'Otro Nombre',
            'dni' => '25.444.555',
        ]);

        $response->assertSessionHasErrors('dni');
        $this->assertSame(1, PersonaAlerta::where('dni', '25444555')->count());
    }

    public function test_permite_cargar_varias_personas_sin_dni(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        PersonaAlerta::factory()->create(['dni' => null, 'apellido_nombre' => 'Sin Datos Uno']);

        $response = $this->actingAs($admin)->post(route('alertas-video.personas.store'), [
            'apellido_nombre' => 'Sin Datos Dos',
        ]);

        $response->assertSessionDoesntHaveErrors('dni');
        $this->assertNotNull(PersonaAlerta::whereNull('dni')->where('apellido_nombre', 'Sin Datos Dos')->first());
    }

    public function test_el_listado_solo_muestra_activas_por_defecto(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        PersonaAlerta::factory()->create(['apellido_nombre' => 'Activa Visible', 'activo' => true]);
        PersonaAlerta::factory()->create(['apellido_nombre' => 'Inactiva Oculta', 'activo' => false]);

        $response = $this->actingAs($admin)->get(route('alertas-video.personas.index'));

        $response->assertSee('Activa Visible');
        $response->assertDontSee('Inactiva Oculta');
    }

    public function test_puede_marcar_una_persona_como_inactiva_y_queda_en_el_historial(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        $persona = PersonaAlerta::factory()->create(['activo' => true]);

        $response = $this->actingAs($admin)->post(route('alertas-video.personas.activo', $persona), [
            'activo' => 0,
            'comentario' => 'Caso resuelto.',
        ]);

        $response->assertRedirect(route('alertas-video.personas.show', $persona));
        $this->assertFalse($persona->fresh()->activo);
        $this->assertSame(1, AlertaMovimiento::where('movable_id', $persona->id)
            ->where('movable_type', PersonaAlerta::class)
            ->where('accion', 'CAMBIO_ACTIVO')
            ->count());
    }
}
