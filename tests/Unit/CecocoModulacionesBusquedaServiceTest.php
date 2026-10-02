<?php

namespace Tests\Unit;

use App\Models\EventoCecoco;
use App\Services\CecocoModulacionesBusquedaService;
use App\Services\CecocoModulacionesEscaneoService;
use App\Services\GrabadorTetraService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class CecocoModulacionesBusquedaServiceTest extends TestCase
{
    private CecocoModulacionesBusquedaService $service;
    private EventoCecoco $evento;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-10-02 12:00:00'));
        config(['cache.stores.modulaciones' => ['driver' => 'array', 'serialize' => true],
            'grabador.url' => 'http://recorder.invalid', 'grabador.minutos_antes' => 10]);
        Cache::store('modulaciones')->flush();
        $this->service = new CecocoModulacionesBusquedaService();
        $this->evento = new EventoCecoco();
        $this->evento->forceFill(['id' => 73, 'fecha_hora' => '2026-10-02 10:00:00', 'fecha_cierre' => '2026-10-02 11:00:00']);
        $scan = Mockery::mock(CecocoModulacionesEscaneoService::class);
        $scan->shouldReceive('avanzar')->andReturn(['completa' => true, 'agotada' => true, 'checkpoint' => ['fin' => true], 'archivos' => []]);
        $this->app->instance(CecocoModulacionesEscaneoService::class, $scan);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public static function cantidades(): array
    {
        return array_map(fn ($n) => [$n], [0, 999, 1000, 1001, 1999, 2000, 2501]);
    }

    /** @dataProvider cantidades */
    public function test_paginas_conservadas_y_tope_conservador(int $quantity): void
    {
        $calls = [];
        $recorder = Mockery::mock(GrabadorTetraService::class);
        $recorder->shouldReceive('replayDisponible')->andReturn(true);
        $recorder->shouldReceive('avanzarBusqueda')->andReturnUsing(function ($cursor, $desde, $hasta, $deadline) use ($quantity, &$calls) {
            $calls[] = $cursor;
            $this->assertGreaterThan(microtime(true), $deadline);
            $this->assertLessThanOrEqual(30, $deadline - microtime(true));
            $skip = $cursor['skip'] ?? 0;
            $count = min(1000, max(0, $quantity - $skip));
            $items = [];
            for ($i = 0; $i < $count; $i++) { $items[] = $this->item($skip + $i); }
            return ['modulaciones' => $items, 'agotada' => $count < 1000,
                'cursor' => ['fase' => 'continuesearch', 'searchid' => 'cursor', 'session' => 'dummy', 'skip' => $skip + $count]];
        });
        $this->app->instance(GrabadorTetraService::class, $recorder);
        $state = $this->service->iniciar(10, $this->evento, false);
        for ($i = 0; $i < 8 && !in_array($state['estado'], ['completa', 'limite_alcanzado']); $i++) {
            $state = $this->service->avanzar(10, $this->evento, $state['busqueda_id'], $state['revision']);
        }
        $this->assertCount(min(2000, $quantity), $state['items']);
        $this->assertSame($quantity >= 2000 ? 'limite_alcanzado' : 'completa', $state['estado']);
        if ($quantity >= 1000) { $this->assertSame(1000, $calls[1]['skip']); }
        $public = $this->service->respuesta($state);
        $this->assertArrayNotHasKey('cursor', $public);
        $this->assertSame($quantity >= 2000, str_contains($public['message'], 'puede estar incompleta'));
    }

    public function test_timeout_respuesta_perdida_revision_y_reapertura(): void
    {
        $calls = 0;
        $recorder = Mockery::mock(GrabadorTetraService::class);
        $recorder->shouldReceive('avanzarBusqueda')->andReturnUsing(function ($cursor) use (&$calls) {
            $calls++;
            if ($calls === 2) { throw new \RuntimeException('timeout private credential'); }
            return ['modulaciones' => [$this->item(1)], 'agotada' => false,
                'cursor' => ['fase' => 'getstatus', 'searchid' => 'private', 'session' => 'secret', 'skip' => 1]];
        });
        $this->app->instance(GrabadorTetraService::class, $recorder);
        $start = $this->service->iniciar(10, $this->evento, false);
        $first = $this->service->avanzar(10, $this->evento, $start['busqueda_id'], 0);
        $lost = $this->service->avanzar(10, $this->evento, $start['busqueda_id'], 0);
        $this->assertSame($first, $lost);
        $this->assertSame(1, $calls);
        $paused = $this->service->avanzar(10, $this->evento, $start['busqueda_id'], 1);
        $this->assertSame('pausada', $paused['estado']);
        $this->assertCount(1, $paused['items']);
        $this->assertSame($first['cursor'], $paused['cursor']);
        $this->assertArrayNotHasKey('modulaciones', $this->service->respuesta($paused));
        $this->assertSame($paused, $this->service->iniciar(10, $this->evento, false));
        $this->assertStringNotContainsString('secret', json_encode($this->service->respuesta($paused)));
        $continued = $this->service->avanzar(10, $this->evento, $start['busqueda_id'], 2);
        $this->assertCount(1, $continued['items']);
    }

    public function test_cursor_reiniciado_deduplica_y_pendiente_no_entrega_snapshot(): void
    {
        $recorder = Mockery::mock(GrabadorTetraService::class);
        $recorder->shouldReceive('avanzarBusqueda')->andReturn(
            ['modulaciones' => [$this->item(1)], 'agotada' => false, 'cursor' => ['fase' => 'getstatus', 'searchid' => 'old']],
            ['modulaciones' => [], 'agotada' => false, 'cursor' => ['fase' => 'startsearch']],
            ['modulaciones' => [$this->item(1), $this->item(2)], 'agotada' => false, 'cursor' => ['fase' => 'getstatus', 'searchid' => 'new']]);
        $this->app->instance(GrabadorTetraService::class, $recorder);
        $state = $this->service->iniciar(10, $this->evento, false);
        for ($i = 0; $i < 3; $i++) { $state = $this->service->avanzar(10, $this->evento, $state['busqueda_id'], $state['revision']); }
        $this->assertCount(2, $state['items']);
        $this->assertArrayNotHasKey('modulaciones', $this->service->respuesta($state));
    }

    public function test_respaldo_local_conserva_copias_y_muestra_cantidad_sin_snapshot(): void
    {
        config(['grabador.url' => null]);
        $item = $this->item(1) + ['path' => '/scratch/audio.mp3', 'operador' => 'OP1'];
        $copy = array_merge($item, ['path' => '/scratch/copy.mp3', 'operador' => 'OP2']);
        $scan = Mockery::mock(CecocoModulacionesEscaneoService::class);
        $scan->shouldReceive('avanzar')->andReturn(
            ['completa' => false, 'agotada' => false, 'checkpoint' => ['archivo' => 2], 'archivos' => [$item, $copy], 'paso_ok' => false],
            ['completa' => true, 'agotada' => true, 'checkpoint' => ['fin' => true], 'archivos' => [$item, $copy]]);
        $this->app->instance(CecocoModulacionesEscaneoService::class, $scan);
        $state = $this->service->iniciar(10, $this->evento, false);
        $state = $this->service->avanzar(10, $this->evento, $state['busqueda_id'], 0);
        $this->assertSame('pausada', $state['estado']);
        $this->assertSame(1, $this->service->respuesta($state)['total']);
        $this->assertArrayNotHasKey('modulaciones', $this->service->respuesta($state));
        $state = $this->service->avanzar(10, $this->evento, $state['busqueda_id'], 1);
        $this->assertSame('completa', $state['estado']);
        $this->assertSame(2, $state['items'][0]['copias']);
        $this->assertSame(['OP1', 'OP2'], $state['items'][0]['operadores']);
    }

    public function test_pendiente_pausa_despues_de_dos_minutos_sin_progreso(): void
    {
        $recorder = Mockery::mock(GrabadorTetraService::class);
        $recorder->shouldReceive('avanzarBusqueda')->andReturn(['modulaciones' => [], 'agotada' => false,
            'cursor' => ['fase' => 'getstatus', 'searchid' => 'pending', 'skip' => 0]]);
        $this->app->instance(GrabadorTetraService::class, $recorder);
        $state = $this->service->iniciar(10, $this->evento, false);
        Carbon::setTestNow(now()->addSeconds(121));
        $state = $this->service->avanzar(10, $this->evento, $state['busqueda_id'], 0);
        $this->assertSame('pausada', $state['estado']);
        $this->assertArrayNotHasKey('modulaciones', $this->service->respuesta($state));
    }

    public function test_caducidad_ventana_actualizacion_y_aislamiento(): void
    {
        $first = $this->service->iniciar(10, $this->evento, false);
        $this->assertNull($this->service->estado(11, $this->evento, null));
        $second = $this->service->iniciar(10, $this->evento, true);
        $this->assertNotSame($first['busqueda_id'], $second['busqueda_id']);
        $this->evento->fecha_cierre = Carbon::parse('2026-10-02 11:30:00');
        $this->assertNull($this->service->estado(10, $this->evento, null));
        $this->service->iniciar(10, $this->evento, false);
        Carbon::setTestNow(now()->addMinutes(31));
        $this->assertNull($this->service->estado(10, $this->evento, null));
    }

    public function test_token_ajeno_es_rechazado(): void
    {
        $this->service->iniciar(10, $this->evento, false);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->service->avanzar(10, $this->evento, str_repeat('a', 64), 0);
    }

    public function test_caducidad_de_completas_abiertas_y_cerradas(): void
    {
        foreach ([true => 600, false => 60] as $closed => $ttl) {
            $this->evento->fecha_cierre = $closed ? Carbon::parse('2026-10-02 11:00:00') : null;
            $state = $this->service->iniciar(10, $this->evento, true);
            $state['estado'] = 'completa'; $state['terminada'] = now()->timestamp;
            Cache::store('modulaciones')->put('mod_search:10:73', $state, 1800);
            Carbon::setTestNow(now()->addSeconds($ttl - 1));
            $this->assertNotNull($this->service->estado(10, $this->evento, null));
            Carbon::setTestNow(now()->addSeconds(2));
            $this->assertNull($this->service->estado(10, $this->evento, null));
        }
    }

    public function test_sesion_compartida_no_ejecuta_dos_operaciones(): void
    {
        $recorder = Mockery::mock(GrabadorTetraService::class);
        $recorder->shouldNotReceive('avanzarBusqueda');
        $this->app->instance(GrabadorTetraService::class, $recorder);
        $state = $this->service->iniciar(10, $this->evento, false);
        $lock = Cache::store('modulaciones')->lock('mod_search_recorder:' . hash('sha256', config('grabador.url') . '|' . config('grabador.user')), 45);
        $lock->get();
        try {
            $next = $this->service->avanzar(10, $this->evento, $state['busqueda_id'], 0);
            $this->assertSame($state['cursor'], $next['cursor']);
            $this->assertCount(0, $next['items']);
        } finally { $lock->release(); }
    }

    public function test_lock_no_bloquea_y_no_pierde_estado(): void
    {
        $state = $this->service->iniciar(10, $this->evento, false);
        $lock = Cache::store('modulaciones')->lock('mod_search:10:73:lock', 45);
        $this->assertTrue($lock->get());
        try {
            $this->assertSame($state, $this->service->estado(10, $this->evento, null));
            try { $this->service->avanzar(10, $this->evento, $state['busqueda_id'], 0); $this->fail('Expected conflict'); }
            catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) { $this->assertSame(409, $e->getStatusCode()); }
        } finally { $lock->release(); }
    }

    private function item(int $id): array
    {
        return ['itemid' => $id . '_20261002100000', 'fechaInicio' => '2026-10-02 10:00:00',
            'duracion' => '3', 'canal' => 'TETRA [M1]'];
    }
}
