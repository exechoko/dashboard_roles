<?php

namespace Tests\Feature;

use App\Models\Camara;
use App\Models\User;
use App\Services\CamaraReinicioService;
use App\Services\GeneradorConfigTextos;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SeguridadAuditoriaTest extends TestCase
{
    use DatabaseTransactions;

    private const ROL_SUPER = 'Super Administrador';

    public function test_super_administrador_por_rol_accede_desde_dominio_publico_sin_acceso_externo(): void
    {
        $usuario = $this->usuarioCon([], [self::ROL_SUPER], ['acceso_externo' => false]);

        $this->post('https://car911.stper.com.ar/login', [
            'email' => $usuario->email,
            'password' => 'password',
        ])->assertRedirect();

        $this->assertAuthenticatedAs($usuario);
    }

    public function test_usuario_comun_sin_acceso_externo_es_rechazado_desde_dominio_publico(): void
    {
        $usuario = $this->usuarioCon([], [], ['acceso_externo' => false]);

        $this->post('https://car911.stper.com.ar/login', [
            'email' => $usuario->email,
            'password' => 'password',
        ])->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_usuarios_json_exige_permiso(): void
    {
        $this->actingAs($this->usuarioCon())->get(route('usuarios.json'))->assertForbidden();
        $this->actingAs($this->usuarioCon(['ver-camara']))->get(route('usuarios.json'))->assertForbidden();
        $this->actingAs($this->usuarioCon(['ver-usuario']))->get(route('usuarios.json'))->assertOk();
        $this->actingAs($this->usuarioCon(['ver-plataforma-descargas']))->get(route('usuarios.json'))->assertOk();
    }

    public function test_gate_before_da_todos_los_permisos_al_rol_super_administrador(): void
    {
        $super = $this->usuarioCon([], [self::ROL_SUPER]);

        $this->actingAs($super)->get(route('usuarios.json'))->assertOk();
    }

    public function test_no_se_puede_asignar_un_rol_que_el_editor_no_posee(): void
    {
        $editor = $this->usuarioCon(['editar-usuario'], ['Operador']);
        $objetivo = $this->usuarioCon([], ['Operador']);
        Role::findOrCreate(self::ROL_SUPER, 'web');

        $this->actingAs($editor)
            ->put(route('usuarios.update', $objetivo->id), $this->datosUsuario($objetivo, [self::ROL_SUPER]))
            ->assertSessionHasErrors('roles');

        $this->assertFalse($objetivo->fresh()->hasRole(self::ROL_SUPER));
        $this->assertTrue($objetivo->fresh()->hasRole('Operador'));
    }

    public function test_no_se_puede_auto_promover_a_super_administrador(): void
    {
        $editor = $this->usuarioCon(['editar-usuario'], ['Operador']);
        Role::findOrCreate(self::ROL_SUPER, 'web');

        $this->actingAs($editor)
            ->put(route('usuarios.update', $editor->id), $this->datosUsuario($editor, ['Operador', self::ROL_SUPER]))
            ->assertSessionHasErrors('roles');

        $this->assertFalse($editor->fresh()->hasRole(self::ROL_SUPER));
    }

    public function test_se_pueden_reasignar_roles_que_el_editor_si_posee(): void
    {
        $editor = $this->usuarioCon(['editar-usuario'], ['Operador', 'Administrador']);
        $objetivo = $this->usuarioCon([], ['Operador']);

        $this->actingAs($editor)
            ->put(route('usuarios.update', $objetivo->id), $this->datosUsuario($objetivo, ['Administrador']))
            ->assertSessionHasNoErrors();

        $this->assertTrue($objetivo->fresh()->hasRole('Administrador'));
    }

    public function test_store_con_rol_no_permitido_no_crea_el_usuario(): void
    {
        $editor = $this->usuarioCon(['crear-usuario'], ['Operador']);
        Role::findOrCreate(self::ROL_SUPER, 'web');
        $email = 'nuevo-' . uniqid() . '@example.test';

        $this->actingAs($editor)
            ->post(route('usuarios.store'), [
                'name' => 'Nuevo',
                'apellido' => 'Usuario',
                'lp' => (string) random_int(800000000, 999999999),
                'dni' => '12345678',
                'email' => $email,
                'password' => 'secreto123',
                'confirm-password' => 'secreto123',
                'roles' => [self::ROL_SUPER],
            ])
            ->assertSessionHasErrors('roles');

        $this->assertDatabaseMissing('users', ['email' => $email]);
    }

    public function test_proxy_gis_exige_permiso(): void
    {
        $this->actingAs($this->usuarioCon())
            ->get('/cecoco/gis-proxy/gisviewer/index.html')
            ->assertForbidden();
    }

    /**
     * @dataProvider pathsProxyBloqueados
     */
    public function test_proxy_gis_bloquea_endpoints_administrativos_y_traversal(string $path): void
    {
        Http::fake();
        $usuario = $this->usuarioCon(['ver-mapa-gis-cecoco']);

        $this->actingAs($usuario)->get('/cecoco/gis-proxy/' . $path)->assertForbidden();

        Http::assertNothingSent();
    }

    /**
     * @return array<string, array{string}>
     */
    public static function pathsProxyBloqueados(): array
    {
        return [
            'rest de geoserver' => ['geoserver/rest/workspaces'],
            'rest en mayusculas' => ['GeoServer/REST/workspaces'],
            'web admin' => ['geoserver/web/wicket'],
            'h2 console' => ['gisviewer/h2-console'],
            'traversal con ..' => ['gisviewer/../geoserver/rest'],
            'doble barra' => ['geoserver//rest/workspaces'],
            'punto' => ['geoserver/./rest'],
            'punto codificado' => ['geoserver/%2e%2e/rest'],
            'parametro de path con punto y coma' => ['geoserver;x/rest/workspaces'],
        ];
    }

    /**
     * @dataProvider metodosProxyNoPermitidos
     */
    public function test_proxy_gis_rechaza_metodos_que_modifican_recursos(string $metodo): void
    {
        Http::fake();
        $usuario = $this->usuarioCon(['ver-mapa-gis-cecoco']);

        $this->actingAs($usuario)->call($metodo, '/cecoco/gis-proxy/geoserver/wfs')->assertStatus(405);

        Http::assertNothingSent();
    }

    /**
     * @return array<string, array{string}>
     */
    public static function metodosProxyNoPermitidos(): array
    {
        return [
            'PUT' => ['PUT'],
            'DELETE' => ['DELETE'],
            'PATCH' => ['PATCH'],
        ];
    }

    public function test_proxy_gis_no_bloquea_recursos_legitimos(): void
    {
        Http::fake();
        $usuario = $this->usuarioCon(['ver-mapa-gis-cecoco']);

        $status = $this->actingAs($usuario)->get('/cecoco/gis-proxy/geoserver/wms')->getStatusCode();

        $this->assertNotSame(403, $status);
    }

    public function test_textos_web_sanitiza_el_html_reinyectado_desde_old_input(): void
    {
        $usuario = $this->usuarioCon(['editar-web-textos']);
        $claveHtml = collect(GeneradorConfigTextos::catalogo())
            ->filter(fn (array $meta): bool => ($meta['tipo'] ?? 'text') === 'html')
            ->keys()
            ->first();
        $this->assertNotNull($claveHtml, 'El catálogo no tiene textos de tipo html.');

        $this->actingAs($usuario)
            ->withSession(['_old_input' => ['textos' => [$claveHtml => '<p>ok</p><script>alert("xss")</script>']]])
            ->get(route('web-admin.textos.edit'))
            ->assertOk()
            ->assertDontSee('<script>alert("xss")</script>', false);
    }

    /**
     * @dataProvider tablasReferenciaInvalidas
     */
    public function test_tipo_de_bien_rechaza_nombres_de_tabla_con_caracteres_peligrosos(string $tabla): void
    {
        $super = $this->usuarioCon([], [self::ROL_SUPER]);

        $this->actingAs($super)
            ->post(route('patrimonio.tipos-bien.store'), [
                'nombre' => 'Tipo ' . uniqid(),
                'tiene_tabla_propia' => 1,
                'tabla_referencia' => $tabla,
            ])
            ->assertSessionHasErrors('tabla_referencia');
    }

    /**
     * @return array<string, array{string}>
     */
    public static function tablasReferenciaInvalidas(): array
    {
        return [
            'punto y coma' => ['camaras; DROP TABLE users'],
            'backtick' => ['camaras`'],
            'con base de datos' => ['otra_bd.users'],
            'espacio' => ['camaras users'],
        ];
    }

    public function test_tipo_de_bien_acepta_un_nombre_de_tabla_valido(): void
    {
        $super = $this->usuarioCon([], [self::ROL_SUPER]);

        $this->actingAs($super)
            ->post(route('patrimonio.tipos-bien.store'), [
                'nombre' => 'Tipo ' . uniqid(),
                'tiene_tabla_propia' => 1,
                'tabla_referencia' => 'camaras',
            ])
            ->assertSessionHasNoErrors();
    }

    /**
     * @dataProvider ipsNoPermitidas
     */
    public function test_reiniciar_camara_rechaza_ips_no_permitidas(string $ip): void
    {
        config([
            'services.camaras.user' => 'u',
            'services.camaras.pass' => 'p',
        ]);
        Http::fake();
        $camara = Camara::create(['nombre' => 'Cámara SSRF', 'ip' => $ip]);

        $resultado = app(CamaraReinicioService::class)->reiniciar($camara);

        $this->assertFalse($resultado['ok']);
        Http::assertNothingSent();
    }

    /**
     * @return array<string, array{string}>
     */
    public static function ipsNoPermitidas(): array
    {
        return [
            'loopback' => ['127.0.0.1'],
            'metadatos cloud' => ['169.254.169.254'],
            'cero' => ['0.0.0.0'],
            'host con path' => ['evil.example.com/x?'],
            'ip con credenciales' => ['user@10.0.0.5'],
        ];
    }

    public function test_reiniciar_camara_permite_ips_privadas_de_la_red_interna(): void
    {
        config([
            'services.camaras.user' => 'u',
            'services.camaras.pass' => 'p',
        ]);
        Http::fake(['*/cgi-bin/magicBox.cgi*' => Http::response('', 200)]);
        $camara = Camara::create(['nombre' => 'Cámara LAN', 'ip' => '172.26.100.10']);

        $resultado = app(CamaraReinicioService::class)->reiniciar($camara);

        $this->assertTrue($resultado['ok']);
    }

    /**
     * @param  list<string>  $permisos
     * @param  list<string>  $roles
     * @param  array<string, mixed>  $atributos
     */
    private function usuarioCon(array $permisos = [], array $roles = [], array $atributos = []): User
    {
        $usuario = User::factory()->create($atributos);

        foreach ($permisos as $permiso) {
            $usuario->givePermissionTo(Permission::findOrCreate($permiso, 'web'));
        }

        foreach ($roles as $rol) {
            $usuario->assignRole(Role::findOrCreate($rol, 'web'));
        }

        return $usuario->fresh();
    }

    /**
     * @param  list<string>  $roles
     * @return array<string, mixed>
     */
    private function datosUsuario(User $usuario, array $roles): array
    {
        return [
            'name' => $usuario->name,
            'apellido' => 'Apellido',
            'lp' => $usuario->lp ?? (string) random_int(800000000, 999999999),
            'dni' => '12345678',
            'email' => $usuario->email,
            'roles' => $roles,
        ];
    }
}
