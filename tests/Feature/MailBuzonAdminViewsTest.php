<?php

namespace Tests\Feature;

use App\Models\MailBuzon;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class MailBuzonAdminViewsTest extends TestCase
{
    use DatabaseTransactions;

    private function usuarioAdministrador(): User
    {
        $permiso = Permission::firstOrCreate(['name' => 'administrar-visor-mails', 'guard_name' => 'web']);

        $user = User::factory()->create();
        $user->givePermissionTo($permiso);

        return $user;
    }

    public function test_las_vistas_de_administracion_de_buzones_renderizan(): void
    {
        $admin = $this->usuarioAdministrador();
        $buzon = MailBuzon::create(['nombre' => 'Buzón Vistas', 'carpeta' => 'test_vistas_'.uniqid(), 'activo' => true]);

        $this->actingAs($admin)->get(route('herramientas.mails.buzones.index'))->assertOk();
        $this->actingAs($admin)->get(route('herramientas.mails.buzones.detectar-oficinas'))->assertOk();
        $this->actingAs($admin)->get(route('herramientas.mails.buzones.create'))->assertOk();
        $this->actingAs($admin)->get(route('herramientas.mails.buzones.edit', $buzon))->assertOk();
        $this->actingAs($admin)->get(route('herramientas.mails.buzones.archivos', $buzon))->assertOk();
    }
}
