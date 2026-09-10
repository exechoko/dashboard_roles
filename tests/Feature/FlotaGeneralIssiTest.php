<?php

namespace Tests\Feature;

use App\Models\Destino;
use App\Models\Equipo;
use App\Models\FlotaGeneral;
use App\Models\Historico;
use App\Models\Recurso;
use App\Models\TipoMovimiento;
use App\Models\TipoTerminal;
use App\Models\TipoUso;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FlotaGeneralIssiTest extends TestCase
{
    use DatabaseTransactions;

    public function test_al_registrar_un_movimiento_se_puede_cambiar_el_issi_y_el_id_issi_del_equipo(): void
    {
        [$equipo, $flota] = $this->crearFlotaConEquipo('ISSI-VIEJO', 'ID-ISSI-VIEJO');

        $tipoMovimiento = TipoMovimiento::firstOrCreate(
            ['nombre' => 'Reprogramación'],
            ['detalles' => 'Reprogramación']
        );

        $usuario = $this->usuarioConPermiso('editar-flota');

        $response = $this->actingAs($usuario)->put(route('flota.update', $flota->id), [
            'tipo_movimiento' => json_encode(['id' => $tipoMovimiento->id]),
            'equipo' => $equipo->id,
            'fecha_asignacion' => now()->format('Y-m-d'),
            'observaciones' => 'Test cambio de ISSI',
            'nuevoIssi' => 'ISSI-NUEVO',
            'nuevoNombreIssi' => 'ID-ISSI-NUEVO',
        ]);

        $response->assertRedirect(route('flota.index'));

        $equipo->refresh();
        $this->assertSame('ISSI-NUEVO', $equipo->issi);
        $this->assertSame('ID-ISSI-NUEVO', $equipo->nombre_issi);
        $this->assertStringContainsString('ISSI anterior: ISSI-VIEJO', $equipo->observaciones);
        $this->assertStringContainsString('ISSI nuevo: ISSI-NUEVO', $equipo->observaciones);
        $this->assertStringContainsString('ID ISSI anterior: ID-ISSI-VIEJO', $equipo->observaciones);
        $this->assertStringContainsString('ID ISSI nuevo: ID-ISSI-NUEVO', $equipo->observaciones);
    }

    public function test_se_puede_cambiar_solo_el_id_issi_sin_cambiar_el_issi(): void
    {
        [$equipo, $flota] = $this->crearFlotaConEquipo('ISSI-FIJO', 'ID-ISSI-VIEJO');

        $tipoMovimiento = TipoMovimiento::firstOrCreate(
            ['nombre' => 'Reprogramación'],
            ['detalles' => 'Reprogramación']
        );

        $usuario = $this->usuarioConPermiso('editar-flota');

        $response = $this->actingAs($usuario)->put(route('flota.update', $flota->id), [
            'tipo_movimiento' => json_encode(['id' => $tipoMovimiento->id]),
            'equipo' => $equipo->id,
            'fecha_asignacion' => now()->format('Y-m-d'),
            'observaciones' => 'Test cambio de ID ISSI',
            'nuevoNombreIssi' => 'ID-ISSI-NUEVO',
        ]);

        $response->assertRedirect(route('flota.index'));

        $equipo->refresh();
        $this->assertSame('ISSI-FIJO', $equipo->issi);
        $this->assertSame('ID-ISSI-NUEVO', $equipo->nombre_issi);
        $this->assertStringContainsString('ID ISSI anterior: ID-ISSI-VIEJO', $equipo->observaciones);
        $this->assertStringContainsString('ID ISSI nuevo: ID-ISSI-NUEVO', $equipo->observaciones);
        $this->assertStringNotContainsString('ISSI anterior: ISSI-FIJO', $equipo->observaciones);
    }

    /**
     * @return array{0: Equipo, 1: FlotaGeneral}
     */
    private function crearFlotaConEquipo(string $issi, string $nombreIssi): array
    {
        $destino = Destino::create(['nombre' => 'Destino Test ' . uniqid(), 'tipo' => 'comisaria']);

        $recurso = new Recurso();
        $recurso->nombre = 'Recurso Test ' . uniqid();
        $recurso->destino_id = $destino->id;
        $recurso->save();

        $tipoUso = new TipoUso();
        $tipoUso->uso = 'Móvil';
        $tipoUso->save();

        $tipoTerminal = new TipoTerminal();
        $tipoTerminal->tipo_uso_id = $tipoUso->id;
        $tipoTerminal->marca = 'Marca Test';
        $tipoTerminal->modelo = 'Modelo Test';
        $tipoTerminal->save();

        $equipo = Equipo::create([
            'issi' => $issi,
            'tei' => 'TEI-' . uniqid(),
            'tipo_terminal_id' => $tipoTerminal->id,
        ]);
        $equipo->nombre_issi = $nombreIssi;
        $equipo->save();

        $flota = FlotaGeneral::create([
            'equipo_id' => $equipo->id,
            'recurso_id' => $recurso->id,
            'destino_id' => $destino->id,
            'fecha_asignacion' => now()->subDay()->format('Y-m-d'),
        ]);

        // Histórico previo: el flujo de "Reprogramación" busca el último
        // movimiento del equipo antes de registrar uno nuevo.
        $tipoMovimientoPrevio = TipoMovimiento::firstOrCreate(
            ['nombre' => 'Instalación completa'],
            ['detalles' => 'Instalación completa']
        );

        $historicoPrevio = new Historico();
        $historicoPrevio->equipo_id = $equipo->id;
        $historicoPrevio->recurso_id = $recurso->id;
        $historicoPrevio->destino_id = $destino->id;
        $historicoPrevio->recurso_asignado = $recurso->nombre;
        $historicoPrevio->tipo_movimiento_id = $tipoMovimientoPrevio->id;
        $historicoPrevio->fecha_asignacion = now()->subDay();
        $historicoPrevio->save();

        return [$equipo, $flota];
    }

    private function usuarioConPermiso(string $permiso): User
    {
        $role = Role::firstOrCreate(['name' => 'rol_test_' . $permiso, 'guard_name' => 'web']);
        $role->givePermissionTo(Permission::firstOrCreate(['name' => $permiso, 'guard_name' => 'web']));

        $usuario = User::factory()->create();
        $usuario->assignRole($role);

        return $usuario;
    }
}
