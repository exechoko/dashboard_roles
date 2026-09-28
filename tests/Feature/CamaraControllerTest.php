<?php

namespace Tests\Feature;

use App\Models\Camara;
use App\Models\TipoCamara;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CamaraControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function test_un_usuario_sin_reiniciar_camara_recibe_403_al_reiniciarla(): void
    {
        $usuario = $this->usuarioCon(['ver-camara']);
        $camara = Camara::create(['nombre' => 'Cámara test', 'ip' => '10.0.0.5']);

        $this->actingAs($usuario)->post(route('camaras.reiniciar', $camara))->assertForbidden();
    }

    public function test_un_usuario_con_reiniciar_camara_puede_reiniciarla(): void
    {
        Http::fake(['*/cgi-bin/magicBox.cgi*' => Http::response('', 200)]);

        $usuario = $this->usuarioCon(['ver-camara', 'reiniciar-camara']);
        $camara = Camara::create(['nombre' => 'Cámara test', 'ip' => '10.0.0.5']);

        $this->actingAs($usuario)->post(route('camaras.reiniciar', $camara))
            ->assertRedirect();
    }

    public function test_reiniciar_un_bde_usa_las_credenciales_de_bde_y_no_las_normales(): void
    {
        config([
            'services.camaras.user' => 'user-normal',
            'services.camaras.pass' => 'pass-normal',
            'services.camaras.bde_user' => null,
            'services.camaras.bde_pass' => null,
        ]);
        Http::fake(['*/cgi-bin/magicBox.cgi*' => Http::response('', 200)]);

        $tipoBde = new TipoCamara();
        $tipoBde->tipo = 'BDE (Totem)';
        $tipoBde->save();
        $usuario = $this->usuarioCon(['ver-camara', 'reiniciar-camara']);
        $camara = Camara::create(['nombre' => 'Tótem test', 'ip' => '10.0.0.6', 'tipo_camara_id' => $tipoBde->id]);

        // Sin CAMARA_BDE_USER/PASS configurados, el reinicio de un BDE debe
        // fallar en vez de usar por error las credenciales de las cámaras normales.
        $this->actingAs($usuario)->post(route('camaras.reiniciar', $camara))
            ->assertRedirect()
            ->assertSessionHas('error');

        Http::assertNothingSent();
    }

    private function usuarioCon(array $permisos): User
    {
        $usuario = User::factory()->create();

        foreach ($permisos as $permiso) {
            $usuario->givePermissionTo(Permission::findOrCreate($permiso, 'web'));
        }

        return $usuario->fresh();
    }
}
