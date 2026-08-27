<?php

namespace Tests\Feature;

use App\Jobs\ConsultarTamanoRestauracionesCecoco;
use App\Models\Notificacion;
use App\Services\CecocoExpedienteService;
use App\Services\TelegramService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ConsultarTamanoRestauracionesCecocoNotificacionTest extends TestCase
{
    use DatabaseTransactions;

    private const FLAG_CECOCO = 'cecoco.restauraciones_alerta.cecoco';

    private const FLAG_GPS = 'cecoco.restauraciones_alerta.gps';

    protected function setUp(): void
    {
        parent::setUp();

        Notificacion::categoria(Notificacion::CATEGORIA_INFRAESTRUCTURA)->delete();
        Cache::forget(self::FLAG_CECOCO);
        Cache::forget(self::FLAG_GPS);

        $this->mock(TelegramService::class, function ($mock): void {
            $mock->shouldReceive('enviarMensaje')->andReturn(true);
        });
    }

    protected function tearDown(): void
    {
        Cache::forget(self::FLAG_CECOCO);
        Cache::forget(self::FLAG_GPS);

        parent::tearDown();
    }

    private function mockServicio(float $mb, bool $gps = false): void
    {
        $this->mock(CecocoExpedienteService::class, function ($mock) use ($mb, $gps): void {
            $metodo = $gps ? 'actualizarCacheTamanoBaseRestauracionesGps' : 'actualizarCacheTamanoBaseRestauraciones';
            $mock->shouldReceive($metodo)->andReturn(['mb' => $mb, 'consultado_en' => now()->toIso8601String()]);
        });
    }

    public function test_supera_el_umbral_genera_notificacion_de_alerta(): void
    {
        $this->mockServicio(4500);

        (new ConsultarTamanoRestauracionesCecoco())->handle(
            app(CecocoExpedienteService::class),
            app(TelegramService::class)
        );

        $notificacion = Notificacion::categoria(Notificacion::CATEGORIA_INFRAESTRUCTURA)->latest()->first();

        $this->assertNotNull($notificacion);
        $this->assertSame(Notificacion::TIPO_ALERTA, $notificacion->tipo);
        $this->assertSame('danger', $notificacion->nivel);
        $this->assertStringContainsString('CECOCO', $notificacion->titulo);
    }

    public function test_bajo_el_umbral_no_genera_notificacion(): void
    {
        $this->mockServicio(1000);

        (new ConsultarTamanoRestauracionesCecoco())->handle(
            app(CecocoExpedienteService::class),
            app(TelegramService::class)
        );

        $this->assertSame(0, Notificacion::categoria(Notificacion::CATEGORIA_INFRAESTRUCTURA)->count());
    }

    public function test_no_repite_la_alerta_mientras_sigue_excedido(): void
    {
        $this->mockServicio(4500);
        (new ConsultarTamanoRestauracionesCecoco())->handle(app(CecocoExpedienteService::class), app(TelegramService::class));

        $this->mockServicio(4600);
        (new ConsultarTamanoRestauracionesCecoco())->handle(app(CecocoExpedienteService::class), app(TelegramService::class));

        $this->assertSame(1, Notificacion::categoria(Notificacion::CATEGORIA_INFRAESTRUCTURA)->count());
    }

    public function test_al_bajar_del_umbral_notifica_recuperacion(): void
    {
        $this->mockServicio(4500);
        (new ConsultarTamanoRestauracionesCecoco())->handle(app(CecocoExpedienteService::class), app(TelegramService::class));

        $this->mockServicio(1000);
        (new ConsultarTamanoRestauracionesCecoco())->handle(app(CecocoExpedienteService::class), app(TelegramService::class));

        $ultima = Notificacion::categoria(Notificacion::CATEGORIA_INFRAESTRUCTURA)->latest()->first();
        $this->assertSame(Notificacion::TIPO_RECUPERACION, $ultima->tipo);
        $this->assertSame('success', $ultima->nivel);
    }

    public function test_gps_y_cecoco_se_evaluan_de_forma_independiente(): void
    {
        $this->mockServicio(4500, true);

        (new ConsultarTamanoRestauracionesCecoco(true))->handle(
            app(CecocoExpedienteService::class),
            app(TelegramService::class)
        );

        $notificacion = Notificacion::categoria(Notificacion::CATEGORIA_INFRAESTRUCTURA)->latest()->first();
        $this->assertStringContainsString('GPS', $notificacion->titulo);
    }
}
