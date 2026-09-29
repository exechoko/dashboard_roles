<?php

namespace Tests\Feature;

use App\Models\Destino;
use App\Models\Equipo;
use App\Models\FlotaGeneral;
use App\Models\Recurso;
use App\Models\Sitio;
use App\Models\TipoMovimiento;
use App\Models\User;
use App\Models\Vehiculo;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class OpcionesComboboxTest extends TestCase
{
    use DatabaseTransactions;

    protected function usuarioCon(string ...$permisos): User
    {
        $usuario = User::factory()->create();
        $usuario->givePermissionTo($permisos);

        return $usuario;
    }

    public function test_invitado_no_puede_buscar_equipos(): void
    {
        $this->getJson(route('opciones.flota', 'equipos'))->assertUnauthorized();
    }

    public function test_devuelve_equipos_paginados_con_etiqueta(): void
    {
        $equipo = Equipo::whereNotNull('tei')->first();
        $this->assertNotNull($equipo, 'Se necesita al menos un equipo en la base de pruebas.');

        $respuesta = $this->actingAs($this->usuarioCon('ver-flota'))
            ->getJson(route('opciones.flota', ['catalogo' => 'equipos', 'search' => $equipo->tei, 'per_page' => 5]));

        $respuesta->assertOk()
            ->assertJsonStructure(['data' => [['id', 'label']], 'current_page', 'last_page', 'total']);
        $this->assertContains($equipo->id, collect($respuesta->json('data'))->pluck('id')->all());
    }

    public function test_busqueda_sin_coincidencias_devuelve_vacio(): void
    {
        $this->actingAs($this->usuarioCon('ver-flota'))
            ->getJson(route('opciones.flota', ['catalogo' => 'equipos', 'search' => 'zzz-inexistente-999']))
            ->assertOk()
            ->assertJsonPath('total', 0);
    }

    public function test_la_pagina_precarga_los_equipos_seleccionados_como_chips(): void
    {
        $equipo = Equipo::first();
        $this->assertNotNull($equipo);

        $usuario = $this->usuarioCon('ver-flota');

        $this->actingAs($usuario)
            ->get(route('flota.busquedaAvanzada', ['equipo_id' => [$equipo->id]]))
            ->assertOk()
            ->assertSee('name="equipo_id[]" value="' . $equipo->id . '"', false);
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    public static function catalogosPaginados(): array
    {
        return [
            'recursos' => ['ver-flota', 'opciones.flota', 'recursos'],
            'destinos' => ['ver-flota', 'opciones.flota', 'destinos'],
            'estados' => ['ver-flota', 'opciones.flota', 'estados'],
            'tipos de terminal' => ['ver-flota', 'opciones.flota', 'tipos-terminal'],
            'tipos de movimiento' => ['ver-flota', 'opciones.flota', 'tipos-movimiento'],
            'patrimonio' => ['ver-flota', 'opciones.flota', 'patrimonio'],
            'vehiculos' => ['ver-recurso', 'opciones.recursos', 'vehiculos'],
        ];
    }

    /**
     * @dataProvider catalogosPaginados
     */
    public function test_los_catalogos_devuelven_opciones_paginadas(string $permiso, string $ruta, string $catalogo): void
    {
        $this->actingAs($this->usuarioCon($permiso))
            ->getJson(route($ruta, $catalogo))
            ->assertOk()
            ->assertJsonStructure(['data', 'current_page', 'last_page', 'total']);
    }

    public function test_catalogo_inexistente_devuelve_404(): void
    {
        $this->actingAs($this->usuarioCon('ver-flota'))
            ->getJson('/opciones/flota/inexistente')
            ->assertNotFound();
    }

    public function test_patrimonio_devuelve_lista_fija_filtrable(): void
    {
        $usuario = $this->usuarioCon('ver-flota');

        $this->actingAs($usuario)
            ->getJson(route('opciones.flota', 'patrimonio'))
            ->assertOk()
            ->assertJsonPath('total', 3);

        $this->actingAs($usuario)
            ->getJson(route('opciones.flota', ['catalogo' => 'patrimonio', 'search' => 'pendiente']))
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.id', 'pendiente');
    }

    public function test_la_pagina_precarga_los_filtros_de_catalogos_seleccionados(): void
    {
        $destino = Destino::first();
        $this->assertNotNull($destino);

        $usuario = $this->usuarioCon('ver-flota');

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

        $respuesta = $this->actingAs($this->usuarioCon('ver-flota'))
            ->getJson(route('opciones.flota', ['catalogo' => 'equipos', 'search' => $conFlota->tei, 'sin_flota' => 1]))
            ->assertOk();

        $this->assertNotContains($conFlota->id, collect($respuesta->json('data'))->pluck('id')->all());
    }

    public function test_crear_flota_usa_combobox_para_equipo_y_dependencia(): void
    {
        $usuario = $this->usuarioCon('crear-flota');

        $this->actingAs($usuario)
            ->get(route('flota.create'))
            ->assertOk()
            ->assertSee('id="equipo_wrap"', false)
            ->assertSee('id="dependencia_wrap"', false);
    }

    public function test_editar_flota_precarga_el_equipo_actual(): void
    {
        $flota = FlotaGeneral::first();
        $this->assertNotNull($flota);

        $usuario = $this->usuarioCon('editar-flota');

        $this->actingAs($usuario)
            ->get(route('flota.edit', $flota->id))
            ->assertOk()
            ->assertSee('name="equipo" value="' . $flota->equipo_id . '"', false)
            ->assertSee('id="dependencia_wrap"', false);
    }

    /**
     * @return array<string, array{0: string|null, 1: string, 2: string, 3: int}>
     */
    public static function matrizDePermisos(): array
    {
        return [
            'sin permisos: equipos de flota' => [null, 'opciones.flota', 'equipos', 403],
            'sin permisos: vehiculos de recursos' => [null, 'opciones.recursos', 'vehiculos', 403],
            'sin permisos: destinos de sitios' => [null, 'opciones.sitios', 'destinos', 403],
            'recurso: destinos' => ['crear-recurso', 'opciones.recursos', 'destinos', 200],
            'recurso: vehiculos' => ['crear-recurso', 'opciones.recursos', 'vehiculos', 200],
            'recurso: equipos de flota' => ['crear-recurso', 'opciones.flota', 'equipos', 403],
            'recurso: destinos de sitios' => ['crear-recurso', 'opciones.sitios', 'destinos', 403],
            'sitio: destinos' => ['editar-sitio', 'opciones.sitios', 'destinos', 200],
            'sitio: vehiculos de recursos' => ['editar-sitio', 'opciones.recursos', 'vehiculos', 403],
        ];
    }

    /**
     * @dataProvider matrizDePermisos
     */
    public function test_cada_modulo_solo_accede_a_sus_catalogos(?string $permiso, string $ruta, string $catalogo, int $estado): void
    {
        $usuario = $permiso ? $this->usuarioCon($permiso) : User::factory()->create();

        $this->actingAs($usuario)->getJson(route($ruta, $catalogo))->assertStatus($estado);
    }

    public function test_un_catalogo_fuera_de_la_ruta_del_modulo_devuelve_404(): void
    {
        $this->actingAs($this->usuarioCon('ver-flota'))
            ->getJson('/opciones/sitios/equipos')
            ->assertNotFound();
    }

    public function test_crear_recurso_usa_combobox_para_dependencia_y_vehiculo(): void
    {
        $usuario = $this->usuarioCon('crear-recurso');

        $this->actingAs($usuario)
            ->get(route('recursos.create'))
            ->assertOk()
            ->assertSee('id="dependencia_wrap"', false)
            ->assertSee('id="vehiculo_wrap"', false);
    }

    public function test_editar_recurso_precarga_dependencia_y_vehiculo(): void
    {
        $recurso = Recurso::whereNotNull('vehiculo_id')->first();
        $this->assertNotNull($recurso);

        $usuario = $this->usuarioCon('editar-recurso');

        $this->actingAs($usuario)
            ->get(route('recursos.edit', $recurso->id))
            ->assertOk()
            ->assertSee('name="dependencia" value="' . $recurso->destino_id . '"', false)
            ->assertSee('name="vehiculo" value="' . $recurso->vehiculo_id . '"', false);
    }

    public function test_editar_sitio_precarga_la_dependencia(): void
    {
        $sitio = Sitio::whereNotNull('destino_id')->first();
        $this->assertNotNull($sitio);

        $usuario = $this->usuarioCon('editar-sitio');

        $this->actingAs($usuario)
            ->get(route('sitios.edit', $sitio->id))
            ->assertOk()
            ->assertSee('name="destino_id" value="' . $sitio->destino_id . '"', false);
    }

    public function test_guardar_recurso_con_los_valores_del_combobox(): void
    {
        $destino = Destino::first();
        $vehiculo = Vehiculo::first();
        $this->assertNotNull($destino);
        $this->assertNotNull($vehiculo);

        $usuario = $this->usuarioCon('crear-recurso');
        $nombre = 'Recurso combobox ' . uniqid();

        $this->actingAs($usuario)
            ->post(route('recursos.store'), [
                'dependencia' => $destino->id,
                'vehiculo' => $vehiculo->id,
                'nombre' => $nombre,
            ])
            ->assertRedirect(route('recursos.index'));

        $this->assertDatabaseHas('recursos', [
            'nombre' => $nombre,
            'destino_id' => $destino->id,
            'vehiculo_id' => $vehiculo->id,
        ]);
    }

    public function test_guardar_recurso_sin_vehiculo_deja_el_campo_vacio(): void
    {
        $destino = Destino::first();
        $usuario = $this->usuarioCon('crear-recurso');
        $nombre = 'Recurso sin vehiculo ' . uniqid();

        $this->actingAs($usuario)
            ->post(route('recursos.store'), ['dependencia' => $destino->id, 'vehiculo' => '', 'nombre' => $nombre])
            ->assertRedirect(route('recursos.index'));

        $this->assertDatabaseHas('recursos', ['nombre' => $nombre, 'vehiculo_id' => null]);
    }

    public function test_guardar_recurso_sin_dependencia_falla_la_validacion(): void
    {
        $usuario = $this->usuarioCon('crear-recurso');

        $this->actingAs($usuario)
            ->post(route('recursos.store'), ['dependencia' => '', 'nombre' => 'X ' . uniqid()])
            ->assertSessionHasErrors('dependencia');
    }

    public function test_actualizar_sitio_con_la_dependencia_del_combobox(): void
    {
        $sitio = Sitio::first();
        $otroDestino = Destino::where('id', '!=', $sitio->destino_id)->first();
        $this->assertNotNull($sitio);
        $this->assertNotNull($otroDestino);

        $usuario = $this->usuarioCon('editar-sitio');

        $this->actingAs($usuario)
            ->put(route('sitios.update', $sitio->id), [
                'nombre' => $sitio->nombre,
                'localidad' => $sitio->localidad ?: 'Paraná',
                'destino_id' => $otroDestino->id,
                'activo' => 1,
            ])
            ->assertRedirect(route('sitios.index'));

        $this->assertDatabaseHas('sitio', ['id' => $sitio->id, 'destino_id' => $otroDestino->id]);
    }

    public function test_actualizar_sitio_sin_dependencia_falla_la_validacion(): void
    {
        $sitio = Sitio::first();
        $usuario = $this->usuarioCon('editar-sitio');

        $this->actingAs($usuario)
            ->put(route('sitios.update', $sitio->id), [
                'nombre' => $sitio->nombre,
                'localidad' => 'Paraná',
                'destino_id' => '',
                'activo' => 1,
            ])
            ->assertSessionHasErrors('destino_id');
    }

    public function test_guardar_flota_con_equipo_y_dependencia_del_combobox(): void
    {
        $equipo = Equipo::first()->replicate();
        $equipo->tei = 'TEST' . random_int(100000000, 999999999);
        $equipo->issi = 'T' . random_int(1000000, 9999999);
        $equipo->save();
        $destino = Destino::first();
        $recurso = Recurso::where('multi_equipos', true)->first() ?? Recurso::first();
        $movimiento = TipoMovimiento::whereNotIn('nombre', ['Movimiento patrimonial', 'Instalación completa'])->first();

        $usuario = $this->usuarioCon('crear-flota');

        $this->actingAs($usuario)
            ->post(route('flota.store'), [
                'tipo_movimiento' => $movimiento->id,
                'equipo' => $equipo->id,
                'dependencia' => $destino->id,
                'recurso' => $recurso->id,
                'fecha_asignacion' => now()->format('Y-m-d\TH:i'),
            ])
            ->assertRedirect(route('flota.index'));

        $this->assertDatabaseHas('flota_general', ['equipo_id' => $equipo->id, 'destino_id' => $destino->id]);
    }

    public function test_guardar_flota_sin_equipo_ni_dependencia_falla_la_validacion(): void
    {
        $usuario = $this->usuarioCon('crear-flota');

        $this->actingAs($usuario)
            ->post(route('flota.store'), [
                'tipo_movimiento' => TipoMovimiento::first()->id,
                'equipo' => '',
                'dependencia' => '',
                'fecha_asignacion' => now()->format('Y-m-d\TH:i'),
            ])
            ->assertSessionHasErrors(['equipo', 'dependencia']);
    }

    public function test_index_de_recursos_usa_combobox_y_filtra_por_dependencia(): void
    {
        $recurso = Recurso::first();
        $this->assertNotNull($recurso);

        $usuario = $this->usuarioCon('ver-recurso');

        $this->actingAs($usuario)
            ->get(route('recursos.index'))
            ->assertOk()
            ->assertSee('id="dependencia_id_wrap"', false)
            ->assertSee('cb-ajax-wrap search-combobox', false);

        $this->actingAs($usuario)
            ->get(route('recursos.index', ['dependencia_id' => $recurso->destino_id]))
            ->assertOk()
            ->assertSee('name="dependencia_id" value="' . $recurso->destino_id . '"', false)
            ->assertSee($recurso->nombre);
    }
}
