<?php

namespace Tests\Feature;

use App\Models\ActivacionTotem;
use App\Models\EventoCecoco;
use App\Models\User;
use App\Services\DetectorActivacionesTotem;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ActivacionTotemTest extends TestCase
{
    use DatabaseTransactions;

    public function test_un_usuario_no_autenticado_no_puede_acceder(): void
    {
        $response = $this->get(route('activaciones-totem.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_un_usuario_autenticado_puede_ver_el_listado(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        $evento = EventoCecoco::factory()->create([
            'descripcion' => 'Activacion del Totem ubicado en Espejo y Crausaz',
        ]);
        ActivacionTotem::create([
            'evento_cecoco_id' => $evento->id,
            'nro_expediente' => $evento->nro_expediente,
            'fecha_evento' => $evento->fecha_hora,
            'palabra_detectada' => 'totem',
            'estado' => ActivacionTotem::ESTADO_PENDIENTE,
        ]);

        $response = $this->actingAs($admin)->get(route('activaciones-totem.index'));

        $response->assertOk()
            ->assertViewIs('activaciones-totem.index')
            ->assertSee('Activaciones Tótem')
            ->assertSee($evento->nro_expediente);
    }

    public function test_el_dashboard_muestra_la_tarjeta_de_activaciones_pendientes(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        $evento = EventoCecoco::factory()->create([
            'descripcion' => 'Activacion del Totem ubicado en Racedo y Blvd',
        ]);
        ActivacionTotem::create([
            'evento_cecoco_id' => $evento->id,
            'nro_expediente' => $evento->nro_expediente,
            'fecha_evento' => $evento->fecha_hora,
            'palabra_detectada' => 'totem',
            'estado' => ActivacionTotem::ESTADO_PENDIENTE,
        ]);

        $response = $this->actingAs($admin)->get(route('home'));

        $response->assertOk()->assertSee('Activaciones Tótem pendientes');
    }

    public function test_esta_vencida_cuando_pasaron_mas_de_6_meses_y_sigue_pendiente_o_descargada(): void
    {
        $vieja = ActivacionTotem::create([
            'evento_cecoco_id' => EventoCecoco::factory()->create()->id,
            'nro_expediente' => '9999901',
            'fecha_evento' => now()->subMonths(7),
            'palabra_detectada' => 'totem',
            'estado' => ActivacionTotem::ESTADO_PENDIENTE,
        ]);

        $reciente = ActivacionTotem::create([
            'evento_cecoco_id' => EventoCecoco::factory()->create()->id,
            'nro_expediente' => '9999902',
            'fecha_evento' => now()->subMonths(2),
            'palabra_detectada' => 'totem',
            'estado' => ActivacionTotem::ESTADO_DESCARGADO,
        ]);

        $this->assertTrue($vieja->esVencida());
        $this->assertFalse($reciente->esVencida());
    }

    public function test_no_esta_vencida_si_esta_descartada_o_eliminada_aunque_sea_vieja(): void
    {
        $descartada = ActivacionTotem::create([
            'evento_cecoco_id' => EventoCecoco::factory()->create()->id,
            'nro_expediente' => '9999903',
            'fecha_evento' => now()->subMonths(8),
            'palabra_detectada' => 'totem',
            'estado' => ActivacionTotem::ESTADO_DESCARTADO,
        ]);

        $eliminada = ActivacionTotem::create([
            'evento_cecoco_id' => EventoCecoco::factory()->create()->id,
            'nro_expediente' => '9999904',
            'fecha_evento' => now()->subMonths(8),
            'palabra_detectada' => 'totem',
            'estado' => ActivacionTotem::ESTADO_ELIMINADO,
        ]);

        $this->assertFalse($descartada->esVencida());
        $this->assertFalse($eliminada->esVencida());
    }

    public function test_scope_vencidas_solo_trae_pendientes_y_descargadas_con_mas_de_6_meses(): void
    {
        ActivacionTotem::create([
            'evento_cecoco_id' => EventoCecoco::factory()->create()->id,
            'nro_expediente' => '9999905',
            'fecha_evento' => now()->subMonths(7),
            'palabra_detectada' => 'totem',
            'estado' => ActivacionTotem::ESTADO_DESCARGADO,
        ]);
        ActivacionTotem::create([
            'evento_cecoco_id' => EventoCecoco::factory()->create()->id,
            'nro_expediente' => '9999906',
            'fecha_evento' => now()->subMonths(1),
            'palabra_detectada' => 'totem',
            'estado' => ActivacionTotem::ESTADO_PENDIENTE,
        ]);

        $this->assertSame(1, ActivacionTotem::vencidas()->whereIn('nro_expediente', ['9999905', '9999906'])->count());
    }

    public function test_marcar_como_eliminado_registra_usuario_y_fecha(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        $activacion = ActivacionTotem::create([
            'evento_cecoco_id' => EventoCecoco::factory()->create()->id,
            'nro_expediente' => '9999907',
            'fecha_evento' => now()->subMonths(7),
            'palabra_detectada' => 'totem',
            'estado' => ActivacionTotem::ESTADO_DESCARGADO,
            'descargado_por' => $admin->id,
            'fecha_descarga' => now()->subMonths(6),
        ]);

        $response = $this->actingAs($admin)
            ->post(route('activaciones-totem.eliminar', $activacion));

        $response->assertRedirect(route('activaciones-totem.index'));
        $this->assertDatabaseHas('activaciones_totem', [
            'id' => $activacion->id,
            'estado' => ActivacionTotem::ESTADO_ELIMINADO,
            'eliminado_por' => $admin->id,
        ]);
        $this->assertNotNull($activacion->fresh()->fecha_eliminado);
        $this->assertFalse($activacion->fresh()->esVencida());
    }

    public function test_detecta_un_evento_con_totem_en_la_descripcion(): void
    {
        $evento = EventoCecoco::factory()->create([
            'descripcion' => 'Se recepciona la activacion del Totem calle Celia Torra, femenina solicita ambulancia',
        ]);

        $creadas = app(DetectorActivacionesTotem::class)->detectar();

        $this->assertSame(1, $creadas);
        $this->assertDatabaseHas('activaciones_totem', [
            'evento_cecoco_id' => $evento->id,
            'nro_expediente' => $evento->nro_expediente,
            'estado' => ActivacionTotem::ESTADO_PENDIENTE,
        ]);
    }

    public function test_detecta_bde_como_palabra_completa(): void
    {
        $evento = EventoCecoco::factory()->create([
            'descripcion' => 'Se activa el BDE ubicado en Artigas y Cochrane, masculino solicita ambulancia',
        ]);

        app(DetectorActivacionesTotem::class)->detectar();

        $this->assertDatabaseHas('activaciones_totem', [
            'evento_cecoco_id' => $evento->id,
            'palabra_detectada' => 'BDE',
        ]);
    }

    public function test_no_detecta_bde_pegado_dentro_de_otra_palabra(): void
    {
        EventoCecoco::factory()->create([
            'descripcion' => 'Se comunica una femenina afuerabde una vivienda en Grabde calle',
        ]);

        $creadas = app(DetectorActivacionesTotem::class)->detectar();

        $this->assertSame(0, $creadas);
        $this->assertDatabaseCount('activaciones_totem', 0);
    }

    public function test_no_detecta_el_boliche_totem(): void
    {
        EventoCecoco::factory()->create([
            'descripcion' => 'Se comunica un masculino solicitando personal en el boliche TOTEM',
        ]);

        $creadas = app(DetectorActivacionesTotem::class)->detectar();

        $this->assertSame(0, $creadas);
        $this->assertDatabaseCount('activaciones_totem', 0);
    }

    public function test_reescanear_no_duplica_ni_resucita_descartados(): void
    {
        $evento = EventoCecoco::factory()->create([
            'descripcion' => 'Activacion del Totem ubicado en Ituzaingo y Sanchez',
        ]);

        app(DetectorActivacionesTotem::class)->detectar();
        $activacion = ActivacionTotem::where('evento_cecoco_id', $evento->id)->firstOrFail();
        $activacion->update(['estado' => ActivacionTotem::ESTADO_DESCARTADO]);

        $creadas = app(DetectorActivacionesTotem::class)->detectar();

        $this->assertSame(0, $creadas);
        $this->assertDatabaseCount('activaciones_totem', 1);
        $this->assertDatabaseHas('activaciones_totem', [
            'evento_cecoco_id' => $evento->id,
            'estado' => ActivacionTotem::ESTADO_DESCARTADO,
        ]);
    }

    public function test_marcar_como_descargado_registra_usuario_y_fecha(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        $evento = EventoCecoco::factory()->create([
            'descripcion' => 'Activacion del Totem ubicado en Racedo y America',
        ]);
        $activacion = ActivacionTotem::create([
            'evento_cecoco_id' => $evento->id,
            'nro_expediente' => $evento->nro_expediente,
            'fecha_evento' => $evento->fecha_hora,
            'palabra_detectada' => 'totem',
            'estado' => ActivacionTotem::ESTADO_PENDIENTE,
        ]);

        $response = $this->actingAs($admin)
            ->put(route('activaciones-totem.update', $activacion), [
                'observaciones' => 'Video descargado del sistema de videovigilancia',
            ]);

        $response->assertRedirect(route('activaciones-totem.index'));
        $this->assertDatabaseHas('activaciones_totem', [
            'id' => $activacion->id,
            'estado' => ActivacionTotem::ESTADO_DESCARGADO,
            'descargado_por' => $admin->id,
            'observaciones' => 'Video descargado del sistema de videovigilancia',
        ]);
        $this->assertNotNull($activacion->fresh()->fecha_descarga);
    }
}
