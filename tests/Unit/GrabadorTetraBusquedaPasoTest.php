<?php

namespace Tests\Unit;

use App\Services\GrabadorTetraService;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Tests\TestCase;

class GrabadorTetraBusquedaPasoTest extends TestCase
{
    public function test_start_status_continue_son_operaciones_independientes(): void
    {
        $history = [];
        $mock = new MockHandler([
            new Response(200, [], json_encode(['searchid' => 's1', 'searchStatus' => 'processing'])),
            new Response(200, [], json_encode(['searchid' => 's1', 'searchStatus' => 'done', 'results' => ['gridRows' => []]])),
        ]);
        $stack = HandlerStack::create($mock); $stack->push(Middleware::history($history));
        $service = new class(new Client(['handler' => $stack])) extends GrabadorTetraService {
            public function __construct(private Client $client) { parent::__construct(); }
            protected function httpClient(): Client { return $this->client; }
        };
        $from = Carbon::parse('2026-10-02 10:00:00'); $to = $from->copy()->addHour();
        $page = $service->avanzarBusqueda(['session' => 'dummy', 'fase' => 'startsearch'], $from, $to, microtime(true) + 30);
        $this->assertFalse($page['agotada']);
        $this->assertSame('getstatus', $page['cursor']['fase']);
        $this->assertCount(1, $history);
        $next = $service->avanzarBusqueda($page['cursor'], $from, $to, microtime(true) + 30);
        $this->assertTrue($next['agotada']);
        $this->assertCount(2, $history);
        parse_str($history[0]['request']->getUri()->getQuery(), $query);
        $this->assertSame('startsearch', $query['action']);
        $this->assertSame('6', $query['MaximumResults']);
        parse_str($history[1]['request']->getUri()->getQuery(), $query);
        $this->assertSame('getstatus', $query['action']);
    }

    public function test_timeout_en_getstatus_reinicia_la_ventana_conservando_skip(): void
    {
        $mock = new MockHandler([new \GuzzleHttp\Exception\ConnectException(
            'cURL error 28', new \GuzzleHttp\Psr7\Request('GET', 'http://recorder.invalid'))]);
        $service = new class(new Client(['handler' => HandlerStack::create($mock)])) extends GrabadorTetraService {
            public function __construct(private Client $client) { parent::__construct(); }
            protected function httpClient(): Client { return $this->client; }
        };
        $page = $service->avanzarBusqueda(['session' => 'dummy', 'fase' => 'getstatus', 'searchid' => 's1', 'skip' => 1000],
            Carbon::now(), Carbon::now(), microtime(true) + 30);
        $this->assertFalse($page['agotada']);
        $this->assertSame([], $page['modulaciones']);
        $this->assertSame(['fase' => 'startsearch', 'skip' => 1000], $page['cursor']);
    }

    public static function respuestasInvalidas(): array
    {
        return [['not json'], [json_encode(['searchid' => 's', 'searchStatus' => 'done'])],
            [json_encode(['searchid' => '0'])], [json_encode(['searchid' => 's', 'searchStatus' => 'error'])]];
    }

    /** @dataProvider respuestasInvalidas */
    public function test_respuesta_invalida_nunca_es_vacio(string $body): void
    {
        $mock = new MockHandler([new Response(200, [], $body)]);
        $service = new class(new Client(['handler' => HandlerStack::create($mock)])) extends GrabadorTetraService {
            public function __construct(private Client $client) { parent::__construct(); }
            protected function httpClient(): Client { return $this->client; }
        };
        $this->expectException(\RuntimeException::class);
        $service->avanzarBusqueda(['session' => 'dummy'], Carbon::now(), Carbon::now(), microtime(true) + 30);
    }

    public function test_timeouts_ajustados_al_presupuesto_sin_cambiar_reproduccion(): void
    {
        $service = new GrabadorTetraService();
        $deadline = new \ReflectionProperty($service, 'searchDeadline');
        $clientMethod = new \ReflectionMethod($service, 'httpClient');
        $deadline->setValue($service, microtime(true) + 1);
        $client = $clientMethod->invoke($service);
        $this->assertLessThanOrEqual(1, $client->getConfig('timeout'));
        $this->assertLessThanOrEqual(1, $client->getConfig('connect_timeout'));
        $deadline->setValue($service, microtime(true) + 30);
        $client = $clientMethod->invoke($service);
        $this->assertEqualsWithDelta(30, $client->getConfig('timeout'), 1);
        $deadline->setValue($service, microtime(true) + 120);
        $this->assertSame(80, $clientMethod->invoke($service)->getConfig('timeout'));
        $deadline->setValue($service, microtime(true) + 30);
        $this->assertSame(3, $client->getConfig('connect_timeout'));
        $deadline->setValue($service, null);
        $this->assertSame((int) config('grabador.timeout', 30), $clientMethod->invoke($service)->getConfig('timeout'));
    }
}
