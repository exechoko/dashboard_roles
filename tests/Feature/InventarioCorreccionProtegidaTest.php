<?php

namespace Tests\Feature;

use App\Models\ArmaTipo;
use App\Models\Personal;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class InventarioCorreccionProtegidaTest extends TestCase
{
    use DatabaseTransactions;

    public function test_cambio_manual_en_funcionario_importado_bloquea_la_sincronizacion_de_inventario(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $tipo = ArmaTipo::create([
            'nombre' => 'Pistola de prueba',
            'activo' => true,
        ]);

        $personal = Personal::create([
            'personal911_id' => 999001,
            'nombre' => 'Juan',
            'apellido' => 'Prueba',
            'lp' => '99001',
            'jerarquia' => 'Sargento',
        ]);

        $personal->cambiarArma(
            'ARMA-LOCAL-1',
            $tipo->id,
            'CHALECO-LOCAL-1',
            now()->toDateString(),
            'Corrección local por dato erróneo en Personal 911',
            true,
            $user->id
        );

        $personal->refresh();

        $this->assertTrue($personal->arma_importacion_bloqueada);
        $this->assertTrue($personal->chaleco_importacion_bloqueada);
        $this->assertSame($user->id, $personal->inventario_bloqueado_por);
        $this->assertSame('ARMA-LOCAL-1', $personal->numeracion_arma);
        $this->assertSame('CHALECO-LOCAL-1', $personal->nro_chaleco);
        $this->assertDatabaseHas('personal_arma_asignaciones', [
            'personal_id' => $personal->id,
            'activa' => true,
            'origen' => 'manual',
        ]);
        $this->assertDatabaseHas('personal_chaleco_asignaciones', [
            'personal_id' => $personal->id,
            'activa' => true,
            'origen' => 'manual',
        ]);
    }
}
