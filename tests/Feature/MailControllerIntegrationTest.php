<?php

namespace Tests\Feature;

use App\Models\MailArchivo;
use App\Models\MailBuzon;
use App\Models\MailMensaje;
use App\Models\User;
use App\Services\Mbox\MboxIndexador;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MailControllerIntegrationTest extends TestCase
{
    use DatabaseTransactions;

    private function indexarFixtureYUsuario(): array
    {
        $role = Role::firstOrCreate(['name' => 'rol_test_mbox_integ', 'guard_name' => 'web']);
        $permiso = Permission::firstOrCreate(['name' => 'ver-visor-mails', 'guard_name' => 'web']);
        $role->givePermissionTo($permiso);

        $buzon = MailBuzon::create([
            'nombre' => 'Buzón Integración',
            'carpeta' => 'test_integ_'.uniqid(),
            'role_id' => $role->id,
            'activo' => true,
        ]);

        $archivo = MailArchivo::create([
            'buzon_id' => $buzon->id,
            'nombre_archivo' => 'prueba.mbox',
            'ruta_absoluta' => base_path('tests/Fixtures/mbox/prueba.mbox'),
            'tamano_bytes' => filesize(base_path('tests/Fixtures/mbox/prueba.mbox')),
            'estado' => 'pendiente',
        ]);

        app(MboxIndexador::class)->indexar($archivo);

        $usuario = User::factory()->create();
        $usuario->assignRole($role);

        return [$usuario, $buzon];
    }

    public function test_el_cuerpo_del_mensaje_de_texto_se_ve_sanitizado(): void
    {
        [$usuario] = $this->indexarFixtureYUsuario();
        $mensaje = MailMensaje::where('message_id', 'msg1@example.com')->firstOrFail();

        $this->actingAs($usuario)
            ->get(route('herramientas.mails.cuerpo', $mensaje))
            ->assertOk()
            ->assertSee('áéíóú', false);
    }

    public function test_el_html_con_script_se_sanea_al_mostrar_el_cuerpo(): void
    {
        [$usuario] = $this->indexarFixtureYUsuario();
        $mensaje = MailMensaje::where('message_id', 'msg2@example.com')->firstOrFail();

        $respuesta = $this->actingAs($usuario)->get(route('herramientas.mails.cuerpo', $mensaje));

        $respuesta->assertOk();
        $respuesta->assertSee('Version en', false);
        $respuesta->assertDontSee('<script', false);
    }

    public function test_se_puede_descargar_el_adjunto_del_mensaje(): void
    {
        [$usuario] = $this->indexarFixtureYUsuario();
        $mensaje = MailMensaje::where('message_id', 'msg3@example.com')->firstOrFail();

        $respuesta = $this->actingAs($usuario)->get(route('herramientas.mails.adjunto', [$mensaje, 0]));

        $respuesta->assertOk();
        $this->assertStringContainsString('documento.pdf', $respuesta->headers->get('Content-Disposition'));
    }

    public function test_se_puede_descargar_el_eml_original(): void
    {
        [$usuario] = $this->indexarFixtureYUsuario();
        $mensaje = MailMensaje::where('message_id', 'msg1@example.com')->firstOrFail();

        $respuesta = $this->actingAs($usuario)->get(route('herramientas.mails.eml', $mensaje));

        $respuesta->assertOk();
        $respuesta->assertHeader('Content-Type', 'message/rfc822');
        $this->assertStringContainsString('Message-ID: <msg1@example.com>', $respuesta->getContent());
    }

    public function test_el_rango_de_fechas_incluye_todo_el_dia_hasta_del_filtro(): void
    {
        [$usuario, $buzon] = $this->indexarFixtureYUsuario();

        // msg2 tiene fecha 2024-01-02 10:00:00: un filtro fecha_desde=fecha_hasta='2024-01-02'
        // debe incluirlo igual (antes se resolvía con whereDate(), ahora con
        // comparaciones directas de datetime + startOfDay()/endOfDay()).
        $respuesta = $this->actingAs($usuario)->get(route('herramientas.mails.index', [
            'buzon_id' => $buzon->id,
            'fecha_desde' => '2024-01-02',
            'fecha_hasta' => '2024-01-02',
        ]));

        $respuesta->assertOk();
        $respuesta->assertSee('Notificacion con HTML');
        $respuesta->assertDontSee('Prueba con acentos');
        $respuesta->assertDontSee('Mensaje con adjunto');
    }
}
