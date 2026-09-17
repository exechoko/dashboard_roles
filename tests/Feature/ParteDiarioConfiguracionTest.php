<?php

namespace Tests\Feature;

use App\Models\Recurso;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ParteDiarioConfiguracionTest extends TestCase
{
    use DatabaseTransactions;

    private function usuario(string $permiso = 'configurar-parte-diario'): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo($permiso);

        return $user;
    }

    private function recursoDeMotos(): Recurso
    {
        return Recurso::query()
            ->whereIn('destino_id', config('flota911.parte.destinos_motos', []))
            ->activos()
            ->firstOrFail();
    }

    public function test_solo_quien_tiene_el_permiso_ve_la_seccion_de_configuracion(): void
    {
        $admin = $this->usuario();
        $admin->givePermissionTo('generar-parte-diario-motos');

        $conPermiso = $this->actingAs($admin)
            ->get(route('flota-911.informes.parte-diario', ['tipo' => 'motos']));
        $conPermiso->assertOk()->assertSee('Configurar recursos de este parte');

        $sinPermiso = $this->actingAs($this->usuario('generar-parte-diario-motos'))
            ->get(route('flota-911.informes.parte-diario', ['tipo' => 'motos']));
        $sinPermiso->assertOk()->assertDontSee('Configurar recursos de este parte');
    }

    public function test_sin_permiso_no_puede_guardar_la_configuracion(): void
    {
        $this->actingAs($this->usuario('generar-parte-diario-motos'))
            ->post(route('flota-911.informes.parte-diario.configuracion.guardar'), [
                'tipo'      => 'motos',
                'incluidos' => [],
            ])
            ->assertForbidden();
    }

    public function test_destildar_un_recurso_lo_saca_del_listado_del_parte(): void
    {
        $this->actingAs($this->usuario());
        $recurso = $this->recursoDeMotos();

        $otrosIds = Recurso::query()
            ->whereIn('destino_id', config('flota911.parte.destinos_motos', []))
            ->activos()
            ->where('id', '!=', $recurso->id)
            ->pluck('id');

        $this->post(route('flota-911.informes.parte-diario.configuracion.guardar'), [
            'tipo'      => 'motos',
            'incluidos' => $otrosIds->all(),
        ])->assertRedirect();

        $this->assertFalse($recurso->fresh()->incluir_en_parte_diario);

        $respuesta = $this->actingAs($this->usuario('generar-parte-diario-motos'))
            ->get(route('flota-911.informes.parte-diario', ['tipo' => 'motos']));

        $respuesta->assertOk()->assertDontSee($recurso->nombre, false);
    }

    public function test_guardar_la_configuracion_de_motos_no_toca_los_moviles(): void
    {
        $this->actingAs($this->usuario());
        $recursoMovil = Recurso::query()
            ->whereIn('destino_id', config('flota911.parte.destinos_moviles', []))
            ->activos()
            ->whereNotNull('vehiculo_id')
            ->firstOrFail();

        $this->post(route('flota-911.informes.parte-diario.configuracion.guardar'), [
            'tipo'      => 'motos',
            'incluidos' => [], // ningún recurso de motos incluido
        ])->assertRedirect();

        $this->assertTrue($recursoMovil->fresh()->incluir_en_parte_diario);
    }

    public function test_volver_a_tildarlo_lo_repone_en_el_listado(): void
    {
        $this->actingAs($this->usuario());
        $recurso = $this->recursoDeMotos();
        $recurso->incluir_en_parte_diario = false;
        $recurso->save();

        $todosLosIds = Recurso::query()
            ->whereIn('destino_id', config('flota911.parte.destinos_motos', []))
            ->activos()
            ->pluck('id');

        $this->post(route('flota-911.informes.parte-diario.configuracion.guardar'), [
            'tipo'      => 'motos',
            'incluidos' => $todosLosIds->all(),
        ])->assertRedirect();

        $this->assertTrue($recurso->fresh()->incluir_en_parte_diario);
    }
}
