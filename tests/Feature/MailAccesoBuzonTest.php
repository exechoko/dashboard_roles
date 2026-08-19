<?php

namespace Tests\Feature;

use App\Models\MailArchivo;
use App\Models\MailBuzon;
use App\Models\MailMensaje;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MailAccesoBuzonTest extends TestCase
{
    use DatabaseTransactions;

    private function crearBuzonConMensaje(string $sufijo): MailMensaje
    {
        $role = Role::firstOrCreate(['name' => "rol_test_mbox_{$sufijo}", 'guard_name' => 'web']);

        $buzon = MailBuzon::create([
            'nombre' => "Buzón {$sufijo}",
            'carpeta' => "test_acceso_{$sufijo}",
            'role_id' => $role->id,
            'activo' => true,
        ]);

        $archivo = MailArchivo::create([
            'buzon_id' => $buzon->id,
            'nombre_archivo' => 'prueba.mbox',
            'ruta_absoluta' => base_path('tests/Fixtures/mbox/prueba.mbox'),
            'tamano_bytes' => 100,
            'estado' => 'indexado',
        ]);

        return MailMensaje::create([
            'buzon_id' => $buzon->id,
            'archivo_id' => $archivo->id,
            'byte_offset' => 0,
            'byte_length' => 100,
            'message_id' => "<{$sufijo}@example.com>",
            'de_email' => 'remitente@example.com',
            'asunto' => "Mensaje de {$sufijo}",
            'carpeta' => 'recibidos',
        ]);
    }

    private function usuarioConRol(string $sufijo): User
    {
        $permiso = Permission::firstOrCreate(['name' => 'ver-visor-mails', 'guard_name' => 'web']);
        $role = Role::firstOrCreate(['name' => "rol_test_mbox_{$sufijo}", 'guard_name' => 'web']);
        $role->givePermissionTo($permiso);

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    public function test_un_usuario_no_puede_ver_un_mensaje_de_un_buzon_de_otro_rol(): void
    {
        $mensajeAjeno = $this->crearBuzonConMensaje('b');
        $usuarioDeA = $this->usuarioConRol('a');

        $this->actingAs($usuarioDeA)
            ->get(route('herramientas.mails.show', $mensajeAjeno))
            ->assertForbidden();
    }

    public function test_el_index_no_permite_seleccionar_un_buzon_ajeno(): void
    {
        $mensajeAjeno = $this->crearBuzonConMensaje('c');
        $usuarioDeA = $this->usuarioConRol('a');

        $this->actingAs($usuarioDeA)
            ->get(route('herramientas.mails.index', ['buzon_id' => $mensajeAjeno->buzon_id]))
            ->assertForbidden();
    }

    public function test_el_usuario_si_puede_ver_el_mensaje_de_su_propio_buzon(): void
    {
        $usuario = $this->usuarioConRol('d');
        $mensajePropio = $this->crearBuzonConMensaje('d');

        $this->actingAs($usuario)
            ->get(route('herramientas.mails.show', $mensajePropio))
            ->assertOk();
    }

    public function test_el_index_solo_lista_los_mensajes_del_propio_buzon(): void
    {
        $usuario = $this->usuarioConRol('e');
        $mensajePropio = $this->crearBuzonConMensaje('e');
        $this->crearBuzonConMensaje('f');

        $this->actingAs($usuario)
            ->get(route('herramientas.mails.index', ['buzon_id' => $mensajePropio->buzon_id]))
            ->assertOk()
            ->assertSee('Mensaje de e')
            ->assertDontSee('Mensaje de f');
    }
}
