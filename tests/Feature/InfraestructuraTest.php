<?php

namespace Tests\Feature;

use App\Models\DispositivoEdificio;
use App\Models\User;
use App\Services\SnmpService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InfraestructuraTest extends TestCase
{
    use DatabaseTransactions;

    // IP de documentación (RFC 5737, TEST-NET-3): válida mas nunca ruteable,
    // así que ping/SNMP fallan de forma determinística sin depender de un
    // equipo real ni de la red del entorno donde corra el test.
    private const IP_NO_RUTEABLE = '203.0.113.5';

    // ── Vistas: permisos por pantalla ───────────────────────────────────

    public function test_invitado_es_redirigido_a_login(): void
    {
        $this->get(route('infraestructura.pcs'))->assertRedirect(route('login'));
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
            'pcs' => ['infraestructura.pcs', 'ver-infraestructura-pcs'],
            'servidores' => ['infraestructura.servidores', 'ver-infraestructura-servidores'],
            'camaras' => ['infraestructura.camaras', 'ver-infraestructura-camaras'],
            'red' => ['infraestructura.red', 'ver-infraestructura-red'],
            'librenms' => ['infraestructura.librenms', 'ver-infraestructura-librenms'],
            'central-telefonica' => ['infraestructura.central-telefonica', 'ver-infraestructura-central-telefonica'],
            'workers' => ['infraestructura.workers', 'ver-infraestructura-workers'],
        ];
    }

    // ── estadoGrupo ──────────────────────────────────────────────────────

    public function test_estado_grupo_invalido_devuelve_404(): void
    {
        $usuario = $this->usuarioConPermiso('ver-infraestructura-pcs');

        $this->actingAs($usuario)
            ->getJson(route('api.infraestructura.estado-grupo', 'no-existe'))
            ->assertNotFound();
    }

    public function test_estado_grupo_sin_ningun_permiso_de_grupo_es_403(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->getJson(route('api.infraestructura.estado-grupo', 'pcs'))
            ->assertForbidden();
    }

    public function test_estado_grupo_combina_inventario_con_la_ultima_lectura_cacheada(): void
    {
        $dispositivo = DispositivoEdificio::create([
            'tipo' => 'pc',
            'nombre' => 'PC-TEST-INFRA',
            'ip' => self::IP_NO_RUTEABLE,
            'oficina' => 'Oficina Test',
            'activo' => true,
        ]);

        Cache::put(SnmpService::CACHE_KEY_ESTADO, [
            'dispositivos' => [
                ['id' => $dispositivo->id, 'estado' => 'alerta', 'latencia_ms' => null, 'cpu_pct' => 91.0, 'ram_pct' => null, 'disco_pct' => null],
            ],
            'consultado_en' => '2026-01-01 10:00:00',
        ], now()->addMinutes(30));

        $usuario = $this->usuarioConPermiso('ver-infraestructura-pcs');

        $respuesta = $this->actingAs($usuario)->getJson(route('api.infraestructura.estado-grupo', 'pcs'));

        $respuesta->assertOk();
        $respuesta->assertJsonPath('consultado_en', '2026-01-01 10:00:00');
        $respuesta->assertJsonFragment(['id' => $dispositivo->id, 'estado' => 'alerta', 'cpu_pct' => 91.0]);
    }

    public function test_estado_grupo_sin_lectura_cacheada_marca_pendiente(): void
    {
        $dispositivo = DispositivoEdificio::create([
            'tipo' => 'pc',
            'nombre' => 'PC-SIN-LECTURA',
            'ip' => self::IP_NO_RUTEABLE,
            'oficina' => 'Oficina Test',
            'activo' => true,
        ]);

        Cache::forget(SnmpService::CACHE_KEY_ESTADO);

        $usuario = $this->usuarioConPermiso('ver-infraestructura-pcs');

        $respuesta = $this->actingAs($usuario)->getJson(route('api.infraestructura.estado-grupo', 'pcs'));

        $respuesta->assertOk();
        $respuesta->assertJsonFragment(['id' => $dispositivo->id, 'estado' => 'pendiente']);
    }

    public function test_estado_grupo_solo_incluye_dispositivos_activos_del_tipo_pedido(): void
    {
        // Nombres únicos (uniqid) para no confundirse con el inventario real
        // ya cargado en dev al inspeccionar la respuesta.
        $sufijo = uniqid('infra-test-');
        DispositivoEdificio::create(['tipo' => 'pc', 'nombre' => "PC-INACTIVA-{$sufijo}", 'ip' => self::IP_NO_RUTEABLE, 'oficina' => 'X', 'activo' => false]);
        DispositivoEdificio::create(['tipo' => 'router', 'nombre' => "ROUTER-{$sufijo}", 'ip' => self::IP_NO_RUTEABLE, 'oficina' => 'X', 'activo' => true]);
        $pcActiva = DispositivoEdificio::create(['tipo' => 'pc', 'nombre' => "PC-ACTIVA-{$sufijo}", 'ip' => self::IP_NO_RUTEABLE, 'oficina' => 'X', 'activo' => true]);

        $usuario = $this->usuarioConPermiso('ver-infraestructura-pcs');

        $respuesta = $this->actingAs($usuario)->getJson(route('api.infraestructura.estado-grupo', 'pcs'));

        $nombres = collect($respuesta->json('dispositivos'))->pluck('nombre')->all();
        $this->assertContains($pcActiva->nombre, $nombres);
        $this->assertNotContains("PC-INACTIVA-{$sufijo}", $nombres);
        $this->assertNotContains("ROUTER-{$sufijo}", $nombres);
    }

    // ── refrescarDispositivo ─────────────────────────────────────────────

    public function test_refrescar_dispositivo_requiere_permiso(): void
    {
        $dispositivo = DispositivoEdificio::create([
            'tipo' => 'pc', 'nombre' => 'PC-REFRESH', 'ip' => self::IP_NO_RUTEABLE, 'oficina' => 'X', 'activo' => true,
        ]);
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->postJson(route('api.infraestructura.refrescar-dispositivo', $dispositivo))
            ->assertForbidden();
    }

    public function test_refrescar_dispositivo_con_ip_invalida_devuelve_422(): void
    {
        $dispositivo = DispositivoEdificio::create([
            'tipo' => 'pc', 'nombre' => 'PC-IP-MALA', 'ip' => '10.175.15.300', 'oficina' => 'X', 'activo' => true,
        ]);
        $usuario = $this->usuarioConPermiso('refrescar-infraestructura');

        $this->actingAs($usuario)
            ->postJson(route('api.infraestructura.refrescar-dispositivo', $dispositivo))
            ->assertStatus(422);
    }

    public function test_refrescar_dispositivo_no_alcanzable_lo_marca_caido_y_actualiza_cache(): void
    {
        $dispositivo = DispositivoEdificio::create([
            'tipo' => 'pc', 'nombre' => 'PC-CAIDA', 'ip' => self::IP_NO_RUTEABLE, 'oficina' => 'X', 'activo' => true,
        ]);
        $usuario = $this->usuarioConPermiso('refrescar-infraestructura');

        $respuesta = $this->actingAs($usuario)
            ->postJson(route('api.infraestructura.refrescar-dispositivo', $dispositivo));

        $respuesta->assertOk();
        $respuesta->assertJsonPath('ok', true);
        $respuesta->assertJsonPath('lectura.estado', 'caido');
        $respuesta->assertJsonPath('lectura.alcanzable', false);

        $cache = Cache::get(SnmpService::CACHE_KEY_ESTADO);
        $this->assertSame('caido', collect($cache['dispositivos'])->firstWhere('id', $dispositivo->id)['estado']);
    }

    // ── Endpoints movidos desde el Dashboard (LibreNMS / Central / Workers) ─

    public function test_estado_cctv_sin_cache_reciente_indica_no_disponible(): void
    {
        Cache::forget(\App\Services\LibreNmsService::CACHE_KEY_ULTIMO_USO);
        Cache::forget(\App\Services\LibreNmsService::CACHE_KEY_CAMARAS);
        $usuario = $this->usuarioConPermiso('ver-infraestructura-librenms');

        $respuesta = $this->actingAs($usuario)->getJson(route('api.infraestructura.estado-cctv'));

        $respuesta->assertOk();
        $respuesta->assertJsonPath('disponible', false);
    }

    public function test_estado_troncales_sin_cache_reciente_indica_no_disponible(): void
    {
        Cache::forget(\App\Services\CentralTelefonicaTroncalesService::CACHE_KEY);
        $usuario = $this->usuarioConPermiso('ver-infraestructura-central-telefonica');

        $respuesta = $this->actingAs($usuario)->getJson(route('api.infraestructura.estado-troncales-central-telefonica'));

        $respuesta->assertOk();
        $respuesta->assertJsonPath('disponible', false);
    }

    public function test_rutas_viejas_del_dashboard_ya_no_existen(): void
    {
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('api.dashboard.estado-cctv'));
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('api.dashboard.workers-status'));
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('api.dashboard.estado-troncales-central-telefonica'));
    }

    // ── Menú ─────────────────────────────────────────────────────────────

    public function test_el_menu_muestra_infraestructura_solo_con_el_permiso(): void
    {
        $sinPermiso = User::factory()->create();
        $this->actingAs($sinPermiso)->get(route('home'))->assertDontSee('Infraestructura');

        $conPermiso = $this->usuarioConPermiso('ver-menu-infraestructura');
        $this->actingAs($conPermiso)->get(route('home'))->assertSee('Infraestructura');
    }

    // ── Comando programado ───────────────────────────────────────────────

    public function test_comando_releva_y_cachea_sin_mandar_telegram(): void
    {
        // tipo=nvr no tiene filas reales cargadas en dev: aísla el comando para
        // que solo procese el dispositivo de prueba y no pinguee todo el
        // inventario real (lento y no determinístico en CI).
        $dispositivo = DispositivoEdificio::create([
            'tipo' => 'nvr', 'nombre' => 'NVR-COMANDO', 'ip' => self::IP_NO_RUTEABLE, 'oficina' => 'X', 'activo' => true,
        ]);

        $this->artisan('infraestructura:monitorear --tipo=nvr --sin-telegram')->assertSuccessful();

        $cache = Cache::get(SnmpService::CACHE_KEY_ESTADO);
        $this->assertNotNull($cache);
        $this->assertSame('caido', collect($cache['dispositivos'])->firstWhere('id', $dispositivo->id)['estado']);

        // --sin-telegram no debe dejar flags de alerta activos
        $this->assertFalse(Cache::has('infraestructura.alerta.' . $dispositivo->id));
    }

    public function test_comando_alerta_una_vez_y_respeta_el_cooldown(): void
    {
        // servidor_nebula: sin filas reales en dev y SÍ elegible para SNMP
        // (a diferencia de nvr, que no está en TIPOS_CON_SO/TIPOS_CON_PUERTOS).
        $dispositivo = DispositivoEdificio::create([
            'tipo' => 'servidor_nebula', 'nombre' => 'SRV-ALERTA', 'ip' => self::IP_NO_RUTEABLE, 'oficina' => 'X', 'activo' => true,
        ]);

        $this->mockearSnmpConCpu(99.0);

        $telegram = $this->mock(\App\Services\TelegramService::class);
        $telegram->expects('enviarMensaje')->once()->andReturn(true);

        $this->artisan('infraestructura:monitorear --tipo=servidor_nebula')->assertSuccessful();

        $this->assertTrue(Cache::has('infraestructura.alerta.' . $dispositivo->id));
        $this->assertTrue(Cache::has('infraestructura.cooldown.' . $dispositivo->id));

        // Segunda corrida dentro del cooldown: no debe volver a alertar (mock
        // sin ->expects adicional detectaría una llamada extra como fallo).
        $this->artisan('infraestructura:monitorear --tipo=servidor_nebula')->assertSuccessful();
    }

    public function test_comando_detecta_recuperacion_y_limpia_los_flags(): void
    {
        $dispositivo = DispositivoEdificio::create([
            'tipo' => 'servidor_nebula', 'nombre' => 'SRV-RECUPERA', 'ip' => self::IP_NO_RUTEABLE, 'oficina' => 'X', 'activo' => true,
        ]);

        Cache::put('infraestructura.alerta.' . $dispositivo->id, true, now()->addDay());

        $this->mockearSnmpConCpu(3.0);

        $telegram = $this->mock(\App\Services\TelegramService::class);
        $telegram->expects('enviarMensaje')->once()->andReturn(true);

        $this->artisan('infraestructura:monitorear --tipo=servidor_nebula')->assertSuccessful();

        $this->assertFalse(Cache::has('infraestructura.alerta.' . $dispositivo->id));
    }

    public function test_comando_no_recupera_dentro_de_la_zona_de_histeresis(): void
    {
        // Umbral 85%, histéresis 5%: 82% ya bajó del umbral de alerta (85) pero
        // sigue arriba del de recuperación (80) — no debe darse por recuperado
        // todavía, para no oscilar alerta/recuperado en el borde.
        $dispositivo = DispositivoEdificio::create([
            'tipo' => 'servidor_nebula', 'nombre' => 'SRV-ZONA-HISTERESIS', 'ip' => self::IP_NO_RUTEABLE, 'oficina' => 'X', 'activo' => true,
        ]);

        Cache::put('infraestructura.alerta.' . $dispositivo->id, true, now()->addDay());

        $this->mockearSnmpConCpu(82.0);

        $telegram = $this->mock(\App\Services\TelegramService::class);
        $telegram->expects('enviarMensaje')->never();

        $this->artisan('infraestructura:monitorear --tipo=servidor_nebula')->assertSuccessful();

        $this->assertTrue(Cache::has('infraestructura.alerta.' . $dispositivo->id));
    }

    public function test_comando_sin_dispositivos_activos_no_falla(): void
    {
        // Tipo válido del enum pero sin filas cargadas en el dev actual: evita
        // tocar los dispositivos reales de otros tipos dentro de la transacción.
        $this->artisan('infraestructura:monitorear --tipo=grabador_nebula --sin-telegram')->assertSuccessful();
    }

    /**
     * Reemplaza el SnmpService real por uno que siempre "pinguea OK" y
     * devuelve el CPU indicado (RAM/disco sin dato), para probar el ciclo de
     * alerta/cooldown/recuperación del comando sin depender de un equipo real.
     */
    private function mockearSnmpConCpu(float $cpuPct): void
    {
        $this->app->bind(SnmpService::class, fn () => new class($cpuPct) extends SnmpService {
            public function __construct(private float $cpuPct)
            {
            }

            public function ping(string $ip): array
            {
                return ['alcanzable' => true, 'latencia_ms' => 5];
            }

            public function consultarMetricas(string $ip): ?array
            {
                return [
                    'cpu_pct' => $this->cpuPct,
                    'ram_pct' => null, 'ram_total_gb' => null, 'ram_usado_gb' => null,
                    'disco_pct' => null, 'disco_total_gb' => null, 'disco_usado_gb' => null,
                ];
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
