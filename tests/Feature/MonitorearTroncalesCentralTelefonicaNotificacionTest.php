<?php

namespace Tests\Feature;

use App\Models\Notificacion;
use App\Services\CentralTelefonicaTroncalesService;
use App\Services\TelegramService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class MonitorearTroncalesCentralTelefonicaNotificacionTest extends TestCase
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

    private function mockTroncales(array $troncales): void
    {
        $this->mock(CentralTelefonicaTroncalesService::class, function ($mock) use ($troncales): void {
            $mock->shouldReceive('obtenerEstadoTroncales')->andReturn($troncales);
        });
    }

    public function test_troncal_caido_genera_notificacion_critica(): void
    {
        Cache::forget('central_telefonica.troncal_caido.TRONCAL-TEST');

        $this->mockTroncales([
            ['nombre' => 'TRONCAL-TEST', 'host' => '10.0.0.50', 'estado' => 'offline', 'latencia_ms' => null],
        ]);

        $this->artisan('central-telefonica:monitorear-troncales')->assertSuccessful();

        $notificacion = Notificacion::categoria(Notificacion::CATEGORIA_INFRAESTRUCTURA)->latest()->first();

        $this->assertNotNull($notificacion);
        $this->assertSame(Notificacion::TIPO_ALERTA, $notificacion->tipo);
        $this->assertSame('danger', $notificacion->nivel);
        $this->assertStringContainsString('TRONCAL-TEST', $notificacion->titulo);

        Cache::forget('central_telefonica.troncal_caido.TRONCAL-TEST');
    }

    public function test_troncal_recuperado_genera_notificacion_de_exito(): void
    {
        Cache::put('central_telefonica.troncal_caido.TRONCAL-TEST2', true, now()->addDay());

        $this->mockTroncales([
            ['nombre' => 'TRONCAL-TEST2', 'host' => '10.0.0.51', 'estado' => 'online', 'latencia_ms' => 12],
        ]);

        $this->artisan('central-telefonica:monitorear-troncales')->assertSuccessful();

        $notificacion = Notificacion::categoria(Notificacion::CATEGORIA_INFRAESTRUCTURA)->latest()->first();

        $this->assertNotNull($notificacion);
        $this->assertSame(Notificacion::TIPO_RECUPERACION, $notificacion->tipo);
        $this->assertSame('success', $notificacion->nivel);

        Cache::forget('central_telefonica.troncal_caido.TRONCAL-TEST2');
    }

    public function test_modo_sin_telegram_no_persiste_notificaciones(): void
    {
        Cache::forget('central_telefonica.troncal_caido.TRONCAL-TEST3');

        $this->mockTroncales([
            ['nombre' => 'TRONCAL-TEST3', 'host' => '10.0.0.52', 'estado' => 'offline', 'latencia_ms' => null],
        ]);

        $this->artisan('central-telefonica:monitorear-troncales', ['--sin-telegram' => true])->assertSuccessful();

        $this->assertSame(0, Notificacion::categoria(Notificacion::CATEGORIA_INFRAESTRUCTURA)->count());

        Cache::forget('central_telefonica.troncal_caido.TRONCAL-TEST3');
    }
}
