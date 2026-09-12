<?php

namespace Tests\Feature;

use App\Models\Camara;
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

    private function usuarioCon(array $permisos): User
    {
        $usuario = User::factory()->create();

        foreach ($permisos as $permiso) {
            $usuario->givePermissionTo(Permission::findOrCreate($permiso, 'web'));
        }

        return $usuario->fresh();
    }
}
