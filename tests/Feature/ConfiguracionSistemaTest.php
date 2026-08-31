<?php

namespace Tests\Feature;

use App\Jobs\GenerarBackupBaseDatos;
use App\Jobs\RestaurarBackupBaseDatos;
use App\Models\User;
use App\Services\BackupBaseDatosService;
use App\Services\EnvEditorService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ConfiguracionSistemaTest extends TestCase
{
    use DatabaseTransactions;

    private ?string $envTemporal = null;

    protected function setUp(): void
    {
        parent::setUp();

        // El estado de backup vive en caché (driver 'array' en testing), que
        // persiste entre tests dentro del mismo proceso de PHPUnit.
        Cache::forget(BackupBaseDatosService::CACHE_ESTADO);
    }

    protected function tearDown(): void
    {
        if ($this->envTemporal && is_file($this->envTemporal)) {
            @unlink($this->envTemporal);
        }

        Cache::forget(BackupBaseDatosService::CACHE_ESTADO);

        parent::tearDown();
    }

    // ── EnvEditorService ─────────────────────────────────────────────────

    public function test_env_editor_preserva_comentarios_y_orden_al_actualizar_una_clave(): void
    {
        $contenido = "APP_NAME=Test\n# comentario\n\nDB_HOST=127.0.0.1\nDB_PASSWORD=secreto\n";
        $servicio = $this->servicioEnvConContenido($contenido);

        $servicio->actualizar(['DB_HOST' => '192.168.1.1']);

        $resultado = file_get_contents($this->envTemporal);
        $this->assertSame("APP_NAME=Test\n# comentario\n\nDB_HOST=192.168.1.1\nDB_PASSWORD=secreto\n", $resultado);
    }

    public function test_env_editor_agrega_una_clave_nueva_al_final(): void
    {
        $servicio = $this->servicioEnvConContenido("APP_NAME=Test\n");

        $servicio->actualizar(['NUEVA_CLAVE' => 'valor']);

        $pares = $servicio->pares();
        $this->assertSame('valor', $pares['NUEVA_CLAVE']);
        $this->assertSame('Test', $pares['APP_NAME']);
    }

    public function test_env_editor_entrecomilla_valores_con_espacios(): void
    {
        $servicio = $this->servicioEnvConContenido("APP_NAME=Test\n");

        $servicio->actualizar(['APP_NAME' => 'Con Espacios']);

        $this->assertSame('Con Espacios', $servicio->pares()['APP_NAME']);
        $this->assertStringContainsString('APP_NAME="Con Espacios"', file_get_contents($this->envTemporal));
    }

    public function test_env_editor_no_toca_lineas_que_no_cambiaron(): void
    {
        $contenido = "A=1\nB=2\nC=3\n";
        $servicio = $this->servicioEnvConContenido($contenido);

        $servicio->actualizar(['B' => '2']); // mismo valor: nada debería cambiar

        $this->assertSame($contenido, file_get_contents($this->envTemporal));
    }

    private function servicioEnvConContenido(string $contenido): EnvEditorService
    {
        $this->envTemporal = tempnam(sys_get_temp_dir(), 'env_test_');
        file_put_contents($this->envTemporal, $contenido);

        return new EnvEditorService($this->envTemporal);
    }

    // ── Permisos de las pantallas ────────────────────────────────────────

    public function test_invitado_es_redirigido_a_login(): void
    {
        $this->get(route('configuracion.env'))->assertRedirect(route('login'));
    }

    /**
     * @dataProvider pantallasYPermisos
     */
    public function test_pantalla_requiere_su_permiso_especifico(string $ruta, string $permiso): void
    {
        $sinPermiso = User::factory()->create();
        $this->actingAs($sinPermiso)->get(route($ruta))->assertForbidden();

        $conPermiso = $this->usuarioConPermiso($permiso);
        $this->actingAs($conPermiso)->get(route($ruta))->assertOk();
    }

    public static function pantallasYPermisos(): array
    {
        return [
            'env'     => ['configuracion.env', 'ver-configuracion-env'],
            'ia'      => ['configuracion.ia', 'ver-configuracion-ia'],
            'workers' => ['configuracion.workers', 'ver-configuracion-workers'],
            'backups' => ['configuracion.backups', 'ver-configuracion-backup'],
        ];
    }

    public function test_crear_rol_muestra_el_grupo_configuracion_del_sistema(): void
    {
        $usuario = $this->usuarioConPermiso('crear-rol');

        $this->actingAs($usuario)
            ->get(route('roles.create'))
            ->assertOk()
            ->assertSee('Configuración del Sistema')
            ->assertSee('Configuracion env critico');
    }

    public function test_editar_rol_muestra_el_grupo_configuracion_del_sistema(): void
    {
        $usuario = $this->usuarioConPermiso('editar-rol');
        $role = Role::firstOrCreate(['name' => 'rol_test_render_editar', 'guard_name' => 'web']);

        $this->actingAs($usuario)
            ->get(route('roles.edit', $role))
            ->assertOk()
            ->assertSee('Configuración del Sistema')
            ->assertSee('Configuracion backup');
    }

    public function test_el_menu_muestra_configuracion_del_sistema_solo_con_permiso(): void
    {
        $sinPermiso = User::factory()->create();
        $this->actingAs($sinPermiso)->get(route('home'))->assertDontSee('Configuración del Sistema');

        $conPermiso = $this->usuarioConPermiso('ver-configuracion-env');
        $this->actingAs($conPermiso)->get(route('home'))->assertSee('Configuración del Sistema');
    }

    // ── Guardado del .env vía HTTP (contra un .env de prueba, no el real) ──

    public function test_guardar_env_sin_permiso_critico_rechaza_tocar_el_grupo_critico(): void
    {
        $this->app->instance(EnvEditorService::class, $this->servicioEnvConContenido("APP_ENV=local\n"));

        $usuario = $this->usuarioConPermiso('editar-configuracion-env');

        $this->actingAs($usuario)
            ->put(route('configuracion.env.update'), ['valores' => ['APP_ENV' => 'production']])
            ->assertSessionHasErrors('valores');

        $this->assertSame('local', (new EnvEditorService($this->envTemporal))->pares()['APP_ENV']);
    }

    public function test_guardar_env_rechaza_clave_desconocida(): void
    {
        $this->app->instance(EnvEditorService::class, $this->servicioEnvConContenido("APP_NAME=Test\n"));

        $usuario = $this->usuarioConPermiso('editar-configuracion-env');

        $this->actingAs($usuario)
            ->put(route('configuracion.env.update'), ['valores' => ['CLAVE_QUE_NO_EXISTE' => 'x']])
            ->assertSessionHasErrors('valores');
    }

    public function test_guardar_ia_actualiza_solo_las_claves_del_grupo_ia(): void
    {
        $this->app->instance(EnvEditorService::class, $this->servicioEnvConContenido("IA_MODEL=viejo\nAPP_NAME=Test\n"));

        $usuario = $this->usuarioConPermiso('editar-configuracion-ia');

        $this->actingAs($usuario)
            ->put(route('configuracion.ia.update'), ['valores' => ['IA_MODEL' => 'nuevo']])
            ->assertSessionHas('success');

        $this->assertSame('nuevo', (new EnvEditorService($this->envTemporal))->pares()['IA_MODEL']);
    }

    public function test_guardar_no_agrega_claves_vacias_para_valores_ausentes_del_env(): void
    {
        // El formulario real manda TODAS las claves del catálogo en cada guardado
        // (incluidas las que nunca estuvieron en el .env y quedan en blanco porque
        // usan el default del config). Guardar no debe crearlas vacías: eso
        // pisaría el default de config con '' (p. ej. (int) env('X', 4000) -> 0).
        $this->app->instance(EnvEditorService::class, $this->servicioEnvConContenido("IA_MODEL=viejo\n"));

        $usuario = $this->usuarioConPermiso('editar-configuracion-ia');

        $this->actingAs($usuario)
            ->put(route('configuracion.ia.update'), [
                'valores' => [
                    'IA_MODEL' => 'nuevo',
                    'OLLAMA_URL' => '', // ausente del .env, campo sin tocar
                ],
            ])
            ->assertSessionHas('success');

        $pares = (new EnvEditorService($this->envTemporal))->pares();
        $this->assertSame('nuevo', $pares['IA_MODEL']);
        $this->assertArrayNotHasKey('OLLAMA_URL', $pares);
    }

    public function test_guardar_ia_rechaza_clave_fuera_del_grupo_ia(): void
    {
        $this->app->instance(EnvEditorService::class, $this->servicioEnvConContenido("APP_NAME=Test\n"));

        $usuario = $this->usuarioConPermiso('editar-configuracion-ia');

        $this->actingAs($usuario)
            ->put(route('configuracion.ia.update'), ['valores' => ['APP_NAME' => 'Otro']])
            ->assertSessionHasErrors('valores');
    }

    // ── BackupBaseDatosService: nombres de archivo ──────────────────────

    public function test_backup_service_rechaza_nombres_con_path_traversal(): void
    {
        $servicio = new BackupBaseDatosService(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'car911_backups_test');

        $this->expectException(RuntimeException::class);
        $servicio->ruta('../../etc/passwd');
    }

    public function test_backup_service_rechaza_extension_distinta_de_sql(): void
    {
        $servicio = new BackupBaseDatosService(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'car911_backups_test');

        $this->expectException(RuntimeException::class);
        $servicio->ruta('equipamiento_20260101_000000.sh');
    }

    public function test_backup_service_rechaza_nombre_de_backup_inexistente(): void
    {
        $servicio = new BackupBaseDatosService(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'car911_backups_test');

        $this->expectException(RuntimeException::class);
        $servicio->ruta('equipamiento_20260101_000000.sql');
    }

    // ── Restaurar: confirmación obligatoria ─────────────────────────────

    public function test_restaurar_backup_rechaza_si_la_confirmacion_no_coincide(): void
    {
        $usuario = $this->usuarioConPermiso('restaurar-configuracion-backup');

        $this->actingAs($usuario)
            ->post(route('configuracion.backups.restaurar', 'equipamiento_20260101_000000.sql'), [
                'confirmacion' => 'nombre-incorrecto',
            ])
            ->assertSessionHasErrors('confirmacion');
    }

    public function test_restaurar_backup_sin_permiso_es_403(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->post(route('configuracion.backups.restaurar', 'equipamiento_20260101_000000.sql'), [
                'confirmacion' => 'equipamiento_20260101_000000.sql',
            ])
            ->assertForbidden();
    }

    // ── Backups: se encolan en un Job, no corren dentro del request ─────
    // (una base grande tarda varios minutos; en producción, atrás de
    // Cloudflare, un request que dure más de ~100s se corta).

    public function test_backup_crear_encola_el_job_y_no_lo_ejecuta_en_el_request(): void
    {
        Queue::fake();
        $this->mockearBinariosDisponibles(true);

        $usuario = $this->usuarioConPermiso('crear-configuracion-backup');

        $this->actingAs($usuario)
            ->post(route('configuracion.backups.crear'), ['nota' => 'test'])
            ->assertSessionHas('success');

        Queue::assertPushed(GenerarBackupBaseDatos::class);
        $this->assertSame('procesando', app(BackupBaseDatosService::class)->estado()['estado'] ?? null);
    }

    public function test_backup_crear_rechaza_si_ya_hay_una_operacion_en_curso(): void
    {
        Queue::fake();
        $this->mockearBinariosDisponibles(true);
        app(BackupBaseDatosService::class)->marcarPendiente('crear');

        $usuario = $this->usuarioConPermiso('crear-configuracion-backup');

        $this->actingAs($usuario)
            ->post(route('configuracion.backups.crear'), [])
            ->assertSessionHas('error');

        Queue::assertNotPushed(GenerarBackupBaseDatos::class);
    }

    public function test_backup_restaurar_encola_el_job_con_confirmacion_correcta(): void
    {
        Queue::fake();
        $usuario = $this->usuarioConPermiso('restaurar-configuracion-backup');

        $this->actingAs($usuario)
            ->post(route('configuracion.backups.restaurar', 'equipamiento_20260101_000000.sql'), [
                'confirmacion' => 'equipamiento_20260101_000000.sql',
            ])
            ->assertSessionHas('success');

        Queue::assertPushed(RestaurarBackupBaseDatos::class);
    }

    public function test_backup_estado_devuelve_inactivo_cuando_no_hay_operacion(): void
    {
        $usuario = $this->usuarioConPermiso('ver-configuracion-backup');

        $this->actingAs($usuario)
            ->getJson(route('configuracion.backups.estado'))
            ->assertOk()
            ->assertJson(['estado' => 'inactivo']);
    }

    public function test_backup_estado_sin_permiso_es_403(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->getJson(route('configuracion.backups.estado'))
            ->assertForbidden();
    }

    private function mockearBinariosDisponibles(bool $disponible): void
    {
        $this->app->bind(BackupBaseDatosService::class, fn () => new class($disponible) extends BackupBaseDatosService {
            public function __construct(private bool $disponible)
            {
                parent::__construct();
            }

            public function binariosDisponibles(): bool
            {
                return $this->disponible;
            }
        });
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
