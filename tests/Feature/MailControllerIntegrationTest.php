<?php

namespace Tests\Feature;

use App\Models\MailArchivo;
use App\Models\MailBuzon;
use App\Models\MailMensaje;
use App\Models\User;
use App\Services\Mbox\MboxIndexador;
use App\Services\Mbox\MboxLector;
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

    public function test_se_puede_mostrar_la_alternativa_de_texto_del_mensaje(): void
    {
        [$usuario] = $this->indexarFixtureYUsuario();
        $mensaje = MailMensaje::where('message_id', 'msg2@example.com')->firstOrFail();

        $this->actingAs($usuario)
            ->get(route('herramientas.mails.cuerpo', [$mensaje, 'formato' => 'texto']))
            ->assertOk()
            ->assertSee('Version en texto plano del aviso.', false);
    }

    public function test_el_html_convierte_los_emails_protegidos_por_cloudflare_a_mailto(): void
    {
        $email = 'contacto@example.com';
        $clave = 0x0c;
        $protegido = sprintf('%02x', $clave);

        foreach (str_split($email) as $caracter) {
            $protegido .= sprintf('%02x', ord($caracter) ^ $clave);
        }

        $html = '<a href="/cdn-cgi/l/email-protection#'.$protegido.'">[email protected]</a>';
        $sanitizado = app(MboxLector::class)->sanitizarHtml($html);

        $this->assertSame('<a href="mailto:contacto@example.com">contacto@example.com</a>', $sanitizado);
    }

    private function protegerEmailCloudflare(string $texto, int $clave = 0x0c): string
    {
        $hex = sprintf('%02x', $clave);

        foreach (str_split($texto) as $caracter) {
            $hex .= sprintf('%02x', ord($caracter) ^ $clave);
        }

        return $hex;
    }

    public function test_el_html_decodifica_data_cfemail_en_span_y_en_enlace(): void
    {
        $hex = $this->protegerEmailCloudflare('contacto@example.com', 0x5a);
        $lector = app(MboxLector::class);

        $this->assertSame(
            'Escribir a contacto@example.com ahora',
            $lector->sanitizarHtml('Escribir a <span class="__cf_email__" data-cfemail="'.$hex.'">[email&#160;protected]</span> ahora')
        );

        $this->assertSame(
            '<a href="mailto:contacto@example.com" class="__cf_email__">contacto@example.com</a>',
            $lector->sanitizarHtml('<a class="__cf_email__" href="/cdn-cgi/l/email-protection" data-cfemail="'.$hex.'">[email protected]</a>')
        );
    }

    public function test_el_html_conserva_el_texto_y_el_asunto_de_enlaces_protegidos(): void
    {
        $hex = $this->protegerEmailCloudflare('contacto@example.com?subject=Hola', 0x21);
        $inner = $this->protegerEmailCloudflare('contacto@example.com', 0x33);
        $html = '<a href="https://sitio.com/cdn-cgi/l/email-protection#'.$hex.'">Escribinos: <span data-cfemail="'.$inner.'">[email protected]</span></a>';

        $this->assertSame(
            '<a href="mailto:contacto@example.com?subject=Hola">Escribinos: contacto@example.com</a>',
            app(MboxLector::class)->sanitizarHtml($html)
        );
    }

    public function test_el_html_deja_intacto_lo_que_no_es_un_email_protegido_valido(): void
    {
        $html = '<a href="/cdn-cgi/l/email-protection#zz">x</a><span data-cfemail="0c0d">[email protected]</span>';

        $this->assertSame($html, app(MboxLector::class)->sanitizarHtml($html));
    }

    public function test_el_cuerpo_de_un_mail_real_muestra_los_emails_de_cloudflare_decodificados(): void
    {
        $span = $this->protegerEmailCloudflare('span@example.com');
        $enlace = $this->protegerEmailCloudflare('enlace@example.com?subject=Hola', 0x41);
        $ruta = tempnam(sys_get_temp_dir(), 'mbox');
        file_put_contents($ruta, implode("\r\n", [
            'From remitente@example.com Mon Jan  1 10:00:00 2024',
            'From: Remitente <remitente@example.com>',
            'To: dest@example.com',
            'Subject: Cloudflare',
            'Date: Mon, 1 Jan 2024 10:00:00 +0000',
            'Message-ID: <cf-e2e@example.com>',
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            '',
            '<p>Mail: <span class="__cf_email__" data-cfemail="'.$span.'">[email&#160;protected]</span></p>'
                .'<a href="/cdn-cgi/l/email-protection#'.$enlace.'">Contacto</a>',
            '',
        ]));

        try {
            $role = Role::firstOrCreate(['name' => 'rol_test_mbox_cf', 'guard_name' => 'web']);
            $role->givePermissionTo(Permission::firstOrCreate(['name' => 'ver-visor-mails', 'guard_name' => 'web']));
            $buzon = MailBuzon::create(['nombre' => 'Buzón CF', 'carpeta' => 'test_cf_'.uniqid(), 'role_id' => $role->id, 'activo' => true]);
            $archivo = MailArchivo::create([
                'buzon_id' => $buzon->id,
                'nombre_archivo' => 'cf.mbox',
                'ruta_absoluta' => $ruta,
                'tamano_bytes' => filesize($ruta),
                'estado' => 'pendiente',
            ]);
            app(MboxIndexador::class)->indexar($archivo);

            $usuario = User::factory()->create();
            $usuario->assignRole($role);
            $mensaje = MailMensaje::where('message_id', 'cf-e2e@example.com')->firstOrFail();

            $this->actingAs($usuario)
                ->get(route('herramientas.mails.cuerpo', $mensaje))
                ->assertOk()
                ->assertSee('Mail: span&#64;example.com', false)
                ->assertSee('href="mailto:enlace&#64;example.com?subject=Hola"', false)
                ->assertDontSee('protected]', false);
        } finally {
            @unlink($ruta);
        }
    }

    public function test_si_el_archivo_mbox_no_existe_se_muestra_un_mensaje_claro(): void
    {
        [$usuario] = $this->indexarFixtureYUsuario();
        $mensaje = MailMensaje::where('message_id', 'msg1@example.com')->firstOrFail();
        $mensaje->archivo->update(['ruta_absoluta' => 'F:\Backup_inexistente\faltante.mbox']);

        $this->actingAs($usuario)
            ->get(route('herramientas.mails.cuerpo', $mensaje))
            ->assertStatus(503)
            ->assertSee('No se puede acceder al archivo de correo', false)
            ->assertSee('faltante.mbox', false);

        $this->actingAs($usuario)
            ->get(route('herramientas.mails.eml', $mensaje))
            ->assertStatus(503);
    }

    public function test_los_emails_se_escriben_con_arroba_como_entidad_para_que_cloudflare_no_los_ofusque(): void
    {
        $html = '<style>@media print { a { color: red } }</style><p>De: <a href="mailto:jdo.familia@jusentrerios.gov.ar">jdo.familia@jusentrerios.gov.ar</a> @usuario</p>';
        $resultado = app(MboxLector::class)->evitarOfuscacionDeCloudflare($html);

        $this->assertStringContainsString('<style>@media print', $resultado);
        $this->assertStringContainsString('href="mailto:jdo.familia&#64;jusentrerios.gov.ar"', $resultado);
        $this->assertStringContainsString('>jdo.familia&#64;jusentrerios.gov.ar</a> @usuario', $resultado);
    }

    public function test_el_cuerpo_y_la_impresion_no_exponen_emails_en_claro_a_cloudflare(): void
    {
        [$usuario] = $this->indexarFixtureYUsuario();
        $mensaje = MailMensaje::where('message_id', 'msg1@example.com')->firstOrFail();

        $this->actingAs($usuario)
            ->get(route('herramientas.mails.imprimir', $mensaje))
            ->assertOk()
            ->assertDontSee('@example.com', false);
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

    public function test_la_vista_de_imprimir_incluye_cabecera_cuerpo_saneado_y_csp(): void
    {
        [$usuario] = $this->indexarFixtureYUsuario();
        $mensaje = MailMensaje::where('message_id', 'msg2@example.com')->firstOrFail();

        $respuesta = $this->actingAs($usuario)->get(route('herramientas.mails.imprimir', $mensaje));

        $respuesta->assertOk();
        $respuesta->assertSee('Notificacion con HTML');
        $respuesta->assertSee('secretaria&#64;example.com', false);
        $respuesta->assertSee('copia&#64;example.com', false);
        $respuesta->assertSee('Version en', false);
        $respuesta->assertDontSee('<script>', false);

        $csp = $respuesta->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("script-src 'nonce-", $csp);

        // El nonce del header CSP tiene que ser el mismo que el del <script> del
        // print automático: si no coinciden, el navegador bloquea el window.print().
        preg_match("/script-src 'nonce-([^']+)'/", $csp, $matchCsp);
        preg_match('/<script nonce="([^"]+)">/', $respuesta->getContent(), $matchScript);
        $this->assertNotEmpty($matchCsp);
        $this->assertNotEmpty($matchScript);
        $this->assertSame($matchCsp[1], $matchScript[1]);
    }
}
