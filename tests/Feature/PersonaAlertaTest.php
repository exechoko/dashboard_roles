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

        $response = $this->actingAs($admin)->post(route('alertas-video.personas.store'), $this->datosBase([
            'apellido_nombre' => 'Gomez Maria',
            'dni' => '30111222',
            'identificado' => '1',
            'foto' => UploadedFile::fake()->image('rostro.jpg'),
        ]));

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

        $response = $this->actingAs($admin)->post(route('alertas-video.personas.store'), $this->datosBase([
            'apellido_nombre' => 'Otro Nombre',
            'dni' => '20333444',
        ]));

        $response->assertSessionHasErrors('dni');
        $this->assertSame(1, PersonaAlerta::where('dni', '20333444')->count());
    }

    public function test_el_dni_se_normaliza_sin_puntos(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();

        $this->actingAs($admin)->post(route('alertas-video.personas.store'), $this->datosBase([
            'apellido_nombre' => 'Con Puntos',
            'dni' => '30.111.222',
        ]));

        $this->assertNotNull(PersonaAlerta::where('dni', '30111222')->first());
    }

    public function test_detecta_duplicado_de_dni_aunque_uno_tenga_puntos(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        PersonaAlerta::factory()->create(['dni' => '25444555']);

        $response = $this->actingAs($admin)->post(route('alertas-video.personas.store'), $this->datosBase([
            'apellido_nombre' => 'Otro Nombre',
            'dni' => '25.444.555',
        ]));

        $response->assertSessionHasErrors('dni');
        $this->assertSame(1, PersonaAlerta::where('dni', '25444555')->count());
    }

    public function test_permite_cargar_varias_personas_sin_dni(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        PersonaAlerta::factory()->create(['dni' => null, 'apellido_nombre' => 'Sin Datos Uno']);

        $response = $this->actingAs($admin)->post(route('alertas-video.personas.store'), $this->datosBase([
            'apellido_nombre' => 'Sin Datos Dos',
        ]));

        $response->assertSessionDoesntHaveErrors('dni');
        $this->assertNotNull(PersonaAlerta::whereNull('dni')->where('apellido_nombre', 'Sin Datos Dos')->first());
    }

    public function test_no_permite_cargar_una_persona_sin_los_campos_obligatorios(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();

        $response = $this->actingAs($admin)->post(route('alertas-video.personas.store'), [
            'apellido_nombre' => 'Sin Datos Obligatorios',
        ]);

        $response->assertSessionHasErrors(['solicitado_por', 'funcionario_carga', 'notificar_a']);
        $this->assertNull(PersonaAlerta::where('apellido_nombre', 'Sin Datos Obligatorios')->first());
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

    public function test_encuentra_coincidencias_por_nombre_parcial_y_en_otro_orden(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        $persona = PersonaAlerta::factory()->create(['apellido_nombre' => 'Gonzalez Maria Fernanda', 'dni' => null]);

        $response = $this->actingAs($admin)->get(route('alertas-video.personas.buscar-coincidencias', [
            'nombre' => 'Maria Gonzalez',
        ]));

        $response->assertOk();
        $response->assertJsonFragment(['id' => $persona->id]);
    }

    public function test_encuentra_coincidencias_por_dni_exacto_con_o_sin_puntos(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        $persona = PersonaAlerta::factory()->create(['dni' => '28999111', 'apellido_nombre' => 'Cualquier Nombre']);

        $response = $this->actingAs($admin)->get(route('alertas-video.personas.buscar-coincidencias', [
            'dni' => '28.999.111',
        ]));

        $response->assertOk();
        $response->assertJsonFragment(['id' => $persona->id]);
    }

    public function test_no_devuelve_coincidencias_sin_datos_suficientes(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        PersonaAlerta::factory()->create(['apellido_nombre' => 'Alguien Registrado']);

        $response = $this->actingAs($admin)->get(route('alertas-video.personas.buscar-coincidencias', [
            'nombre' => 'Al',
        ]));

        $response->assertOk();
        $response->assertJson(['coincidencias' => []]);
    }

    public function test_al_crear_puede_marcarse_inactiva_directamente_sin_registrar_cambio_activo(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();

        $this->actingAs($admin)->post(route('alertas-video.personas.store'), $this->datosBase([
            'apellido_nombre' => 'Caso Cerrado',
            'identificado' => '1',
            'activo' => '0',
        ]));

        $persona = PersonaAlerta::where('apellido_nombre', 'Caso Cerrado')->firstOrFail();
        $this->assertFalse($persona->activo);
        $this->assertSame(0, AlertaMovimiento::where('movable_id', $persona->id)
            ->where('movable_type', PersonaAlerta::class)
            ->where('accion', 'CAMBIO_ACTIVO')
            ->count());
    }

    public function test_al_editar_y_desactivar_queda_registrado_como_cambio_de_estado(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        $persona = PersonaAlerta::factory()->create(['activo' => true]);

        $this->actingAs($admin)->put(route('alertas-video.personas.update', $persona), $this->datosBase([
            'apellido_nombre' => $persona->apellido_nombre,
            'identificado' => '1',
            'activo' => '0',
            'comentario' => 'Identificada por movil 12, sin novedad.',
        ]));

        $this->assertFalse($persona->fresh()->activo);
        $this->assertSame(1, AlertaMovimiento::where('movable_id', $persona->id)
            ->where('movable_type', PersonaAlerta::class)
            ->where('accion', 'CAMBIO_ACTIVO')
            ->count());
    }

    public function test_al_editar_sin_cambiar_el_estado_activo_no_registra_cambio_activo(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        $persona = PersonaAlerta::factory()->create(['activo' => true]);

        $this->actingAs($admin)->put(route('alertas-video.personas.update', $persona), $this->datosBase([
            'apellido_nombre' => $persona->apellido_nombre,
        ]));

        $this->assertTrue($persona->fresh()->activo);
        $this->assertSame(0, AlertaMovimiento::where('movable_id', $persona->id)
            ->where('movable_type', PersonaAlerta::class)
            ->where('accion', 'CAMBIO_ACTIVO')
            ->count());
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function datosBase(array $overrides = []): array
    {
        return array_merge([
            'solicitado_por' => 'Juan Perez',
            'funcionario_carga' => 'Ana Gomez',
            'notificar_a' => 'Guardia CECOCO',
        ], $overrides);
    }
}
