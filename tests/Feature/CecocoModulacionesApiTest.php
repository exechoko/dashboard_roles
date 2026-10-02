<?php

namespace Tests\Feature;

use App\Http\Middleware\RegistrarUsuarioConectado;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\EventoCecoco;
use App\Models\User;
use Illuminate\Auth\Access\Gate as AccessGate;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class CecocoModulacionesApiTest extends TestCase
{
    private bool $allowed = true;
    private User $principal;
    private string $url = '/api/cecoco/eventos/73/modulaciones';

    protected function setUp(): void
    {
        parent::setUp();
        config(['cache.stores.modulaciones' => ['driver' => 'array'], 'cache.default' => 'array']);
        Cache::store('modulaciones')->flush(); Cache::flush();
        $this->withoutMiddleware(RegistrarUsuarioConectado::class);
        $this->principal = (new User())->forceFill(['id' => 10, 'name' => 'Fixture']);
        $this->actingAs($this->principal);
        $gate = new AccessGate($this->app, fn () => $this->principal);
        $gate->define('escuchar-modulaciones-cecoco', fn () => $this->allowed);
        Gate::swap($gate);
        Route::bind('eventoCecoco', fn () => (new EventoCecoco())->forceFill([
            'id' => 73, 'fecha_hora' => '2026-10-02 10:00:00', 'fecha_cierre' => '2026-10-02 11:00:00']));
        $this->app->bind(VerifyCsrfToken::class, fn ($app) => new class($app, $app['encrypter']) extends VerifyCsrfToken {
            protected function runningUnitTests() { return false; }
        });
    }

    private function postOperation(array $body)
    {
        return $this->withSession(['_token' => 'fixture-csrf'])->postJson($this->url, $body, ['X-CSRF-TOKEN' => 'fixture-csrf']);
    }

    public function test_estado_no_inicia_trabajo_y_post_requiere_csrf(): void
    {
        $this->getJson($this->url)->assertOk()->assertJsonPath('busqueda_id', null);
        $this->postJson($this->url, ['operacion' => 'iniciar'])->assertStatus(419);
        $response = $this->postOperation(['operacion' => 'iniciar']);
        $response->assertOk()->assertJsonPath('estado', 'buscando')->assertJsonPath('total', 0);
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $response->assertJsonMissingPath('modulaciones');
    }

    public function test_permiso_en_cada_operacion(): void
    {
        $this->allowed = false;
        $this->getJson($this->url)->assertForbidden();
        $this->postOperation(['operacion' => 'iniciar'])->assertForbidden();
        $this->postOperation(['operacion' => 'avanzar', 'busqueda_id' => str_repeat('a', 64), 'revision' => 0])->assertForbidden();
    }

    public function test_periodos_y_protocolo_antiguo_se_rechazan(): void
    {
        $this->getJson($this->url . '?cola=[]&total=1999')->assertStatus(409)->assertJsonPath('recargar', true);
        $this->postOperation(['operacion' => 'iniciar', 'desde' => '1900-01-01'])->assertStatus(409);
    }

    public function test_token_de_otro_usuario_no_avanza(): void
    {
        $token = $this->postOperation(['operacion' => 'iniciar'])->json('busqueda_id');
        $this->principal = (new User())->forceFill(['id' => 11, 'name' => 'Other fixture']);
        $this->actingAs($this->principal);
        $this->postOperation(['operacion' => 'iniciar'])->assertOk();
        $this->postOperation(['operacion' => 'avanzar', 'busqueda_id' => $token, 'revision' => 0])->assertNotFound();
    }

    public function test_limite_de_inicios_por_usuario(): void
    {
        for ($i = 0; $i < 10; $i++) { $this->postOperation(['operacion' => 'iniciar', 'actualizar' => true])->assertOk(); }
        $this->postOperation(['operacion' => 'iniciar'])->assertStatus(429);
    }

    public function test_limite_de_avances_por_usuario(): void
    {
        $token = $this->postOperation(['operacion' => 'iniciar'])->json('busqueda_id');
        $service = app(\App\Services\CecocoModulacionesBusquedaService::class);
        $evento = (new EventoCecoco())->forceFill(['id' => 73, 'fecha_hora' => '2026-10-02 10:00:00', 'fecha_cierre' => '2026-10-02 11:00:00']);
        $state = $service->recuperar(10, $evento, $token);
        $mock = \Mockery::mock(\App\Services\CecocoModulacionesBusquedaService::class)->makePartial();
        $mock->shouldReceive('avanzar')->times(60)->andReturn($state);
        $this->app->instance(\App\Services\CecocoModulacionesBusquedaService::class, $mock);
        for ($i = 0; $i < 60; $i++) {
            $this->postOperation(['operacion' => 'avanzar', 'busqueda_id' => $token, 'revision' => 0])->assertOk();
        }
        $this->postOperation(['operacion' => 'avanzar', 'busqueda_id' => $token, 'revision' => 0])->assertStatus(429);
    }
}
