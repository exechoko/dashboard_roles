<?php

namespace Tests\Feature;

use App\Models\Equipo;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class BusquedaAvanzadaEquiposTest extends TestCase
{
    use DatabaseTransactions;

    protected function usuarioConPermisoDeFlota(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo('ver-flota');

        return $user;
    }

    public function test_invitado_no_puede_buscar_equipos(): void
    {
        $this->getJson(route('flota.busquedaAvanzada.equipos'))->assertUnauthorized();
    }

    public function test_devuelve_equipos_paginados_con_etiqueta(): void
    {
        $equipo = Equipo::whereNotNull('tei')->first();
        $this->assertNotNull($equipo, 'Se necesita al menos un equipo en la base de pruebas.');

        $respuesta = $this->actingAs($this->usuarioConPermisoDeFlota())
            ->getJson(route('flota.busquedaAvanzada.equipos', ['search' => $equipo->tei, 'per_page' => 5]));

        $respuesta->assertOk()
            ->assertJsonStructure(['data' => [['id', 'label']], 'current_page', 'last_page', 'total']);
        $this->assertContains($equipo->id, collect($respuesta->json('data'))->pluck('id')->all());
    }

    public function test_busqueda_sin_coincidencias_devuelve_vacio(): void
    {
        $this->actingAs($this->usuarioConPermisoDeFlota())
            ->getJson(route('flota.busquedaAvanzada.equipos', ['search' => 'zzz-inexistente-999']))
            ->assertOk()
            ->assertJsonPath('total', 0);
    }

    public function test_la_pagina_precarga_los_equipos_seleccionados_como_chips(): void
    {
        $equipo = Equipo::first();
        $this->assertNotNull($equipo);

        $user = User::factory()->create();
        $user->givePermissionTo('ver-flota');

        $this->actingAs($user)
            ->get(route('flota.busquedaAvanzada', ['equipo_id' => [$equipo->id]]))
            ->assertOk()
            ->assertSee('name="equipo_id[]" value="' . $equipo->id . '"', false);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function catalogosPaginados(): array
    {
        return [
            'recursos' => ['recursos'],
            'destinos' => ['destinos'],
            'estados' => ['estados'],
            'tipos de terminal' => ['tipos-terminal'],
            'tipos de movimiento' => ['tipos-movimiento'],
        ];
    }

    /**
     * @dataProvider catalogosPaginados
     */
    public function test_los_catalogos_devuelven_opciones_paginadas(string $catalogo): void
    {
        $this->actingAs($this->usuarioConPermisoDeFlota())
            ->getJson(route('flota.busquedaAvanzada.opciones', $catalogo))
            ->assertOk()
            ->assertJsonStructure(['data', 'current_page', 'last_page', 'total']);
    }

    public function test_catalogo_inexistente_devuelve_404(): void
    {
        $this->actingAs($this->usuarioConPermisoDeFlota())
            ->getJson(route('flota.busquedaAvanzada.opciones', 'inexistente'))
            ->assertNotFound();
    }

    public function test_patrimonio_devuelve_lista_plana_filtrable(): void
    {
        $usuario = $this->usuarioConPermisoDeFlota();

        $this->actingAs($usuario)
            ->getJson(route('flota.busquedaAvanzada.opciones', 'patrimonio'))
            ->assertOk()
            ->assertJsonCount(3);

        $this->actingAs($usuario)
            ->getJson(route('flota.busquedaAvanzada.opciones', ['catalogo' => 'patrimonio', 'search' => 'pendiente']))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', 'pendiente');
    }

    public function test_la_pagina_precarga_los_filtros_de_catalogos_seleccionados(): void
    {
        $destino = \App\Models\Destino::first();
        $this->assertNotNull($destino);

        $usuario = $this->usuarioConPermisoDeFlota();

        $this->actingAs($usuario)
            ->get(route('flota.busquedaAvanzada', ['destino_id' => [$destino->id], 'estado_patrimonial' => 'pendiente']))
            ->assertOk()
            ->assertSee('name="destino_id[]" value="' . $destino->id . '"', false)
            ->assertSee('name="estado_patrimonial" value="pendiente"', false);
    }

    public function test_filtro_sin_flota_excluye_equipos_ya_asignados(): void
    {
        $conFlota = Equipo::has('flota_general')->first();
        $this->assertNotNull($conFlota);

        $respuesta = $this->actingAs($this->usuarioConPermisoDeFlota())
            ->getJson(route('flota.busquedaAvanzada.equipos', ['search' => $conFlota->tei, 'sin_flota' => 1]))
            ->assertOk();

        $this->assertNotContains($conFlota->id, collect($respuesta->json('data'))->pluck('id')->all());
    }

    public function test_crear_flota_usa_combobox_para_equipo_y_dependencia(): void
    {
        $usuario = User::factory()->create();
        $usuario->givePermissionTo('crear-flota');

        $this->actingAs($usuario)
            ->get(route('flota.create'))
            ->assertOk()
            ->assertSee('id="equipo_wrap"', false)
            ->assertSee('id="dependencia_wrap"', false);
    }

    public function test_editar_flota_precarga_el_equipo_actual(): void
    {
        $flota = \App\Models\FlotaGeneral::first();
        $this->assertNotNull($flota);

        $usuario = User::factory()->create();
        $usuario->givePermissionTo('editar-flota');

        $this->actingAs($usuario)
            ->get(route('flota.edit', $flota->id))
            ->assertOk()
            ->assertSee('name="equipo" value="' . $flota->equipo_id . '"', false)
            ->assertSee('id="dependencia_wrap"', false);
    }
}
