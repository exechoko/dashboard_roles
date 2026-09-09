<?php

namespace Tests\Feature;

use App\Models\Notificacion;
use App\Services\LibreNmsService;
use App\Services\TelegramService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class MonitorearCpuVideoNotificacionTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Notificacion::categoria(Notificacion::CATEGORIA_INFRAESTRUCTURA)->delete();
        $this->mock(TelegramService::class, function ($mock): void {
            $mock->shouldReceive('enviarMensaje')->andReturn(true);
        });
    }

    private function mockDispositivos(array $dispositivos): void
    {
        $this->mock(LibreNmsService::class, function ($mock) use ($dispositivos): void {
            $mock->shouldReceive('obtenerUsoCpuGrupo')->andReturn($dispositivos);
        });
    }

    public function test_equipo_sobre_el_umbral_genera_notificacion_de_alerta(): void
    {
        Cache::forget('librenms.cpu_alerta.900');
        Cache::forget('librenms.cpu_cooldown.900');

        $this->mockDispositivos([
            ['device_id' => 900, 'hostname' => 'CCTV-900', 'promedio' => 85, 'maximo' => 95, 'nucleos' => 4],
        ]);

        $this->artisan('librenms:monitorear-cpu', ['--umbral' => 60])->assertSuccessful();

        $notificacion = Notificacion::categoria(Notificacion::CATEGORIA_INFRAESTRUCTURA)->latest()->first();

        $this->assertNotNull($notificacion);
        $this->assertSame(Notificacion::TIPO_ALERTA, $notificacion->tipo);
        $this->assertSame('warning', $notificacion->nivel);
        $this->assertStringContainsString('CCTV-900', $notificacion->titulo);

        Cache::forget('librenms.cpu_alerta.900');
        Cache::forget('librenms.cpu_cooldown.900');
    }

    public function test_equipo_recuperado_genera_notificacion_de_exito(): void
    {
        Cache::put('librenms.cpu_alerta.901', true, now()->addDay());
        Cache::forget('librenms.cpu_cooldown.901');

        $this->mockDispositivos([
            ['device_id' => 901, 'hostname' => 'CCTV-901', 'promedio' => 10, 'maximo' => 20, 'nucleos' => 4],
        ]);

        $this->artisan('librenms:monitorear-cpu', ['--umbral' => 60])->assertSuccessful();

        $notificacion = Notificacion::categoria(Notificacion::CATEGORIA_INFRAESTRUCTURA)->latest()->first();

        $this->assertNotNull($notificacion);
        $this->assertSame(Notificacion::TIPO_RECUPERACION, $notificacion->tipo);
        $this->assertSame('success', $notificacion->nivel);

        Cache::forget('librenms.cpu_alerta.901');
        Cache::forget('librenms.cpu_cooldown.901');
    }

    public function test_modo_sin_telegram_no_persiste_notificaciones(): void
    {
        Cache::forget('librenms.cpu_alerta.902');
        Cache::forget('librenms.cpu_cooldown.902');

        $this->mockDispositivos([
            ['device_id' => 902, 'hostname' => 'CCTV-902', 'promedio' => 85, 'maximo' => 95, 'nucleos' => 4],
        ]);

        $this->artisan('librenms:monitorear-cpu', ['--umbral' => 60, '--sin-telegram' => true])->assertSuccessful();

        $this->assertSame(0, Notificacion::categoria(Notificacion::CATEGORIA_INFRAESTRUCTURA)->count());

        Cache::forget('librenms.cpu_alerta.902');
        Cache::forget('librenms.cpu_cooldown.902');
    }
}
