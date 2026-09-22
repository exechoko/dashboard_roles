<?php

namespace Tests\Feature;

use App\Models\ArmaTipo;
use App\Models\Personal;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ArmaPersonalControllerTest extends TestCase
{
    use DatabaseTransactions;

    private function crearFuncionario(): Personal
    {
        do {
            $lp = (string) random_int(10000, 99999);
        } while (Personal::withTrashed()->where('lp', $lp)->exists());

        return Personal::create([
            'personal911_id' => random_int(900000, 999999),
            'nombre' => 'Funcionario',
            'apellido' => 'De Prueba '.uniqid(),
            'lp' => $lp,
            'jerarquia' => 'Sargento',
        ]);
    }

    public function test_ya_no_existen_las_rutas_de_alta_baja_o_restauracion_manual(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('editar-personal', 'web'));
        $this->actingAs($user);

        $personal = $this->crearFuncionario();

        $this->get('/armas/personal/create')->assertNotFound();
        $this->post('/armas/personal', ['nombre' => 'X'])->assertMethodNotAllowed();
        $this->delete("/armas/personal/{$personal->id}")->assertMethodNotAllowed();
        $this->post("/armas/personal/{$personal->id}/restaurar")->assertNotFound();
    }

    public function test_update_solo_corrige_arma_y_chaleco_no_datos_de_identidad(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('editar-personal', 'web'));
        $this->actingAs($user);

        $personal = $this->crearFuncionario();
        $jerarquiaOriginal = $personal->jerarquia;
        $tipo = ArmaTipo::query()->activos()->first() ?? ArmaTipo::create(['nombre' => 'Pistola de prueba', 'activo' => true]);

        $response = $this->put("/armas/personal/{$personal->id}", [
            'numeracion_arma' => 'ABC123',
            'arma_tipo_id' => $tipo->id,
            'nro_chaleco' => 'CH-1',
            'motivo_cambio' => 'Corrección de prueba',
            // Intento de colar campos de identidad que ya no deberían aceptarse.
            'jerarquia' => 'Comisario',
            'dni' => '99999999',
        ]);

        $response->assertRedirect(route('armas.personal.index'));

        $personal->refresh();
        $this->assertSame('ABC123', $personal->numeracion_arma);
        $this->assertSame($tipo->id, $personal->arma_tipo_id);
        $this->assertSame('CH-1', $personal->nro_chaleco);
        $this->assertSame($jerarquiaOriginal, $personal->jerarquia);
        $this->assertTrue((bool) $personal->arma_importacion_bloqueada);
    }

    public function test_update_requiere_motivo_de_correccion(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('editar-personal', 'web'));
        $this->actingAs($user);

        $personal = $this->crearFuncionario();
        $tipo = ArmaTipo::query()->activos()->first() ?? ArmaTipo::create(['nombre' => 'Pistola de prueba', 'activo' => true]);

        $response = $this->put("/armas/personal/{$personal->id}", [
            'numeracion_arma' => 'ABC123',
            'arma_tipo_id' => $tipo->id,
        ]);

        $response->assertSessionHasErrors('motivo_cambio');
    }

    public function test_index_no_muestra_boton_de_nuevo_funcionario(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('ver-personal', 'web'));
        $this->actingAs($user);

        $response = $this->get('/armas/personal');

        $response->assertOk();
        $response->assertDontSee('Nuevo Funcionario');
    }
}
