<?php

namespace Tests\Feature;

use App\Models\Destino;
use App\Models\Recurso;
use App\Models\RecursoTransferencia;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class RecursoTransferenciaTest extends TestCase
{
    use DatabaseTransactions;

    private const DIVISION_911_ID = 42;

    private function usuario(string ...$permisos): User
    {
        $user = User::factory()->create();
        foreach ($permisos as $permiso) {
            $user->givePermissionTo($permiso);
        }

        return $user;
    }

    private function recursoActivoDe911(): Recurso
    {
        $destinoIds = Destino::findOrFail(self::DIVISION_911_ID)->getDestinosHijosRecursivo();

        return Recurso::query()
            ->whereIn('destino_id', $destinoIds)
            ->whereNotNull('vehiculo_id')
            ->whereNull('fecha_transferencia')
            ->firstOrFail();
    }

    public function test_el_rol_de_gestion_reporta_la_transferencia_sin_afectar_al_recurso(): void
    {
        $recurso = $this->recursoActivoDe911();
        $vehiculoIdOriginal = $recurso->vehiculo_id;

        $this->actingAs($this->usuario('gestionar-flota-911'))
            ->post(route('flota-911.transferencias.store', $recurso->id), [
                'fecha_transferencia' => '2099-03-01',
                'observaciones'       => 'Reemplazado por unidad nueva',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('recurso_transferencias', [
            'recurso_id' => $recurso->id,
            'estado'     => RecursoTransferencia::ESTADO_PENDIENTE,
        ]);

        $recurso->refresh();
        $this->assertNull($recurso->fecha_transferencia);
        $this->assertSame($vehiculoIdOriginal, $recurso->vehiculo_id);
    }

    public function test_reportar_sin_permiso_de_gestion_devuelve_403(): void
    {
        $recurso = $this->recursoActivoDe911();

        $this->actingAs($this->usuario())
            ->post(route('flota-911.transferencias.store', $recurso->id), [
                'fecha_transferencia' => '2099-03-01',
            ])
            ->assertForbidden();
    }

    public function test_no_se_puede_reportar_dos_veces_el_mismo_recurso(): void
    {
        $recurso = $this->recursoActivoDe911();
        $gestor = $this->usuario('gestionar-flota-911');

        $payload = ['fecha_transferencia' => '2099-03-01'];

        $this->actingAs($gestor)->post(route('flota-911.transferencias.store', $recurso->id), $payload);
        $this->actingAs($gestor)->post(route('flota-911.transferencias.store', $recurso->id), $payload)
            ->assertSessionHas('error');

        $this->assertSame(1, RecursoTransferencia::where('recurso_id', $recurso->id)->count());
    }

    public function test_el_admin_confirma_y_el_recurso_queda_transferido_sin_liberar_el_vehiculo(): void
    {
        $recurso = $this->recursoActivoDe911();
        $vehiculoIdOriginal = $recurso->vehiculo_id;

        $transferencia = RecursoTransferencia::create([
            'recurso_id'          => $recurso->id,
            'vehiculo_id'         => $recurso->vehiculo_id,
            'fecha_transferencia' => '2099-03-01',
            'estado'              => RecursoTransferencia::ESTADO_PENDIENTE,
            'user_id_reporte'     => $this->usuario('gestionar-flota-911')->id,
        ]);

        $this->actingAs($this->usuario('confirmar-transferencia-recurso'))
            ->patch(route('flota-911.transferencias.confirmar', $transferencia->id), [
                'fecha_transferencia' => '2099-03-05',
                'reparticion_texto'   => 'Jefatura Departamental X',
            ])
            ->assertRedirect();

        $transferencia->refresh();
        $recurso->refresh();

        $this->assertSame(RecursoTransferencia::ESTADO_CONFIRMADA, $transferencia->estado);
        $this->assertNotNull($recurso->fecha_transferencia);
        $this->assertSame('Jefatura Departamental X', $recurso->reparticion_transferencia);
        $this->assertSame($vehiculoIdOriginal, $recurso->vehiculo_id);
    }

    public function test_el_admin_rechaza_y_el_recurso_sigue_activo(): void
    {
        $recurso = $this->recursoActivoDe911();

        $transferencia = RecursoTransferencia::create([
            'recurso_id'          => $recurso->id,
            'fecha_transferencia' => '2099-03-01',
            'estado'              => RecursoTransferencia::ESTADO_PENDIENTE,
            'user_id_reporte'     => $this->usuario('gestionar-flota-911')->id,
        ]);

        $this->actingAs($this->usuario('confirmar-transferencia-recurso'))
            ->patch(route('flota-911.transferencias.rechazar', $transferencia->id), [
                'motivo_rechazo' => 'Fue un error de carga',
            ])
            ->assertRedirect();

        $transferencia->refresh();
        $recurso->refresh();

        $this->assertSame(RecursoTransferencia::ESTADO_RECHAZADA, $transferencia->estado);
        $this->assertNull($recurso->fecha_transferencia);
    }

    public function test_los_scopes_activos_y_transferidos_separan_los_recursos(): void
    {
        $recurso = $this->recursoActivoDe911();
        $recurso->forceFill(['fecha_transferencia' => now()])->save();

        $this->assertTrue(Recurso::transferidos()->whereKey($recurso->id)->exists());
        $this->assertFalse(Recurso::activos()->whereKey($recurso->id)->exists());
    }

    public function test_reactivar_limpia_los_datos_de_transferencia(): void
    {
        $recurso = $this->recursoActivoDe911();
        $recurso->forceFill([
            'fecha_transferencia'       => now(),
            'reparticion_transferencia' => 'Otra repartición',
        ])->save();

        $this->actingAs($this->usuario('confirmar-transferencia-recurso'))
            ->patch(route('flota-911.transferencias.reactivar', $recurso->id))
            ->assertRedirect();

        $recurso->refresh();
        $this->assertNull($recurso->fecha_transferencia);
        $this->assertNull($recurso->reparticion_transferencia);
    }

    public function test_la_pantalla_de_transferencias_renderiza_con_las_dependencias_jerarquizadas(): void
    {
        $recurso = $this->recursoActivoDe911();

        RecursoTransferencia::create([
            'recurso_id'          => $recurso->id,
            'fecha_transferencia' => '2099-03-01',
            'estado'              => RecursoTransferencia::ESTADO_PENDIENTE,
            'user_id_reporte'     => $this->usuario('gestionar-flota-911')->id,
        ]);

        $this->actingAs($this->usuario('ver-flota-911', 'confirmar-transferencia-recurso'))
            ->get(route('flota-911.transferencias.index'))
            ->assertOk()
            ->assertSee(' › ', false);
    }

    public function test_el_listado_de_recursos_oculta_los_transferidos_salvo_con_el_filtro(): void
    {
        $recurso = $this->recursoActivoDe911();
        $recurso->forceFill(['fecha_transferencia' => now()])->save();

        $user = $this->usuario('ver-recurso');

        $this->actingAs($user)->get(route('recursos.index'))
            ->assertOk()
            ->assertDontSee($recurso->nombre);

        $this->actingAs($user)->get(route('recursos.index', ['estado' => 'transferidos']))
            ->assertOk()
            ->assertSee($recurso->nombre);
    }
}
