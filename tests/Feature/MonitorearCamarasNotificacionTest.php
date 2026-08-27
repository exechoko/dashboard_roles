<?php

namespace Tests\Feature;

use App\Models\Notificacion;
use App\Services\LibreNmsService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class MonitorearCamarasNotificacionTest extends TestCase
{
    use DatabaseTransactions;

    private const CACHE_KEY = 'librenms.camaras.ultimo_offline_count';

    protected function setUp(): void
    {
        parent::setUp();

        Cache::forget(self::CACHE_KEY);
        Notificacion::categoria(Notificacion::CATEGORIA_CAMARAS_CCTV)->delete();
    }

    private function mockEstado(int $total, int $caidas): void
    {
        $offline = array_map(
            fn (int $i) => ['device_id' => $i, 'nombre' => "CAM-{$i}", 'ip' => "10.0.0.{$i}", 'caida_hace' => '1m'],
            range(1, $caidas)
        );

        $this->mock(LibreNmsService::class, function ($mock) use ($total, $offline): void {
            $mock->shouldReceive('obtenerEstadoCamaras')->andReturn(['total' => $total, 'offline' => $offline]);
        });
    }

    public function test_primera_corrida_solo_establece_base_sin_notificar(): void
    {
        $this->mockEstado(300, 3);

        $this->artisan('librenms:monitorear-camaras')->assertSuccessful();

        $this->assertSame(0, Notificacion::categoria(Notificacion::CATEGORIA_CAMARAS_CCTV)->count());
    }

    public function test_aumento_de_caidas_genera_una_sola_notificacion_resumen(): void
    {
        $this->mockEstado(300, 3);
        $this->artisan('librenms:monitorear-camaras')->assertSuccessful();

        $this->mockEstado(300, 12);
        $this->artisan('librenms:monitorear-camaras')->assertSuccessful();

        $notificaciones = Notificacion::categoria(Notificacion::CATEGORIA_CAMARAS_CCTV)->get();
        $this->assertCount(1, $notificaciones);
        $this->assertSame(Notificacion::TIPO_ALERTA, $notificaciones->first()->tipo);
        $this->assertSame('danger', $notificaciones->first()->nivel);
    }

    public function test_recuperacion_total_genera_notificacion_de_exito(): void
    {
        $this->mockEstado(300, 5);
        $this->artisan('librenms:monitorear-camaras')->assertSuccessful();

        $this->mockEstado(300, 0);
        $this->artisan('librenms:monitorear-camaras')->assertSuccessful();

        $ultima = Notificacion::categoria(Notificacion::CATEGORIA_CAMARAS_CCTV)->latest()->first();
        $this->assertSame(Notificacion::TIPO_RECUPERACION, $ultima->tipo);
        $this->assertSame('success', $ultima->nivel);
    }

    public function test_sin_cambios_no_repite_notificacion(): void
    {
        $this->mockEstado(300, 4);
        $this->artisan('librenms:monitorear-camaras')->assertSuccessful();

        $this->mockEstado(300, 4);
        $this->artisan('librenms:monitorear-camaras')->assertSuccessful();

        $this->assertSame(0, Notificacion::categoria(Notificacion::CATEGORIA_CAMARAS_CCTV)->count());
    }
}
