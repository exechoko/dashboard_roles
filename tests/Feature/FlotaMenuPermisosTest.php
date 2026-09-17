<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * El menú "Flota 911" es un único @canany que agrupa secciones con permisos
 * bien granulares: cada rol nuevo (Operador Patrulla/Motopatrulla, Jefe
 * Sección) sólo debe ver los ítems para los que realmente tiene permiso.
 */
class FlotaMenuPermisosTest extends TestCase
{
    use DatabaseTransactions;

    private function usuario(string ...$permisos): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo($permisos);

        return $user;
    }

    public function test_operador_de_moviles_solo_ve_su_propio_parte_en_el_menu(): void
    {
        $respuesta = $this->actingAs($this->usuario('generar-parte-diario-moviles'))
            ->get(route('flota-911.informes.parte-diario', 'moviles'));

        $respuesta->assertOk()
            ->assertSee('Flota 911')
            ->assertSee('Parte de Móviles')
            ->assertDontSee('Parte de Motopatrullas')
            ->assertDontSee('Historial partes diarios')
            ->assertDontSee('Estado Flota')
            ->assertDontSee('Préstamos')
            ->assertDontSee('Transferencias')
            ->assertDontSee('Solicitudes bitácora');
    }

    public function test_operador_de_motos_no_ve_el_parte_de_moviles(): void
    {
        $respuesta = $this->actingAs($this->usuario('generar-parte-diario-motos'))
            ->get(route('flota-911.informes.parte-diario', 'motos'));

        $respuesta->assertOk()
            ->assertSee('Parte de Motopatrullas')
            ->assertDontSee('Parte de Móviles');
    }

    public function test_jefe_seccion_ve_todas_las_secciones_que_tiene_habilitadas(): void
    {
        $jefe = $this->usuario(
            'ver-flota-911',
            'generar-parte-diario-moviles',
            'generar-parte-diario-motos',
            'ver-historial-parte-diario',
            'ver-prestamos-flota-911',
            'ver-transferencias-flota-911',
            'ver-bitacora-solicitudes-flota-911',
        );

        $respuesta = $this->actingAs($jefe)->get(route('flota-911.dashboard'));

        $respuesta->assertOk()
            ->assertSee('Parte de Móviles')
            ->assertSee('Parte de Motopatrullas')
            ->assertSee('Historial partes diarios')
            ->assertSee('Estado Flota')
            ->assertSee('Préstamos')
            ->assertSee('Transferencias')
            ->assertSee('Solicitudes bitácora');
    }

    public function test_sin_ningun_permiso_de_flota_911_no_aparece_el_menu(): void
    {
        $respuesta = $this->actingAs($this->usuario())->get(route('home'));

        $respuesta->assertOk()->assertDontSee('Flota 911');
    }
}
