<?php

namespace Tests\Feature;

use App\Events\ChatMensajeEnviado;
use App\Listeners\EnviarPushMensajeChat;
use App\Models\ChatConversacion;
use App\Models\PushSubscription;
use App\Models\User;
use App\Services\WebPushService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Mockery\MockInterface;
use Tests\TestCase;

class EnviarPushMensajeChatTest extends TestCase
{
    use DatabaseTransactions;

    public function test_envia_push_al_destinatario_que_no_esta_en_linea(): void
    {
        [$emisor, $destinatario, $conversacion, $mensaje] = $this->crearConversacionConMensaje();

        Cache::forget("chat.online.{$destinatario->id}");

        $this->mock(WebPushService::class, function (MockInterface $mock) use ($destinatario, $mensaje, $conversacion) {
            $mock->shouldReceive('enviarATodasLasSuscripciones')
                ->once()
                ->withArgs(fn (User $user, array $payload) => $user->id === $destinatario->id
                    && $payload['title'] === trim($mensaje->usuario->name . ' ' . $mensaje->usuario->apellido)
                    && str_contains($payload['url'], "/movil/chat/{$conversacion->id}"));
        });

        app(EnviarPushMensajeChat::class)->handle(new ChatMensajeEnviado($mensaje, $conversacion));
    }

    public function test_no_envia_push_si_el_destinatario_esta_en_linea(): void
    {
        [$emisor, $destinatario, $conversacion, $mensaje] = $this->crearConversacionConMensaje();

        Cache::put("chat.online.{$destinatario->id}", true, now()->addSeconds(90));

        $this->mock(WebPushService::class, function (MockInterface $mock) {
            $mock->shouldNotReceive('enviarATodasLasSuscripciones');
        });

        app(EnviarPushMensajeChat::class)->handle(new ChatMensajeEnviado($mensaje, $conversacion));
    }

    public function test_no_envia_push_al_propio_emisor(): void
    {
        [$emisor, $destinatario, $conversacion, $mensaje] = $this->crearConversacionConMensaje();

        Cache::forget("chat.online.{$emisor->id}");
        Cache::put("chat.online.{$destinatario->id}", true, now()->addSeconds(90));

        $this->mock(WebPushService::class, function (MockInterface $mock) {
            $mock->shouldNotReceive('enviarATodasLasSuscripciones');
        });

        app(EnviarPushMensajeChat::class)->handle(new ChatMensajeEnviado($mensaje, $conversacion));
    }

    public function test_store_guarda_la_suscripcion_del_usuario_autenticado(): void
    {
        $usuario = User::factory()->create();

        $response = $this->actingAs($usuario)->postJson(route('movil.push.store'), [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc123',
            'keys' => ['p256dh' => str_repeat('a', 40), 'auth' => str_repeat('b', 20)],
        ]);

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseHas('push_subscriptions', [
            'user_id' => $usuario->id,
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc123',
        ]);
    }

    public function test_destroy_borra_solo_la_suscripcion_del_usuario_autenticado(): void
    {
        $usuario = User::factory()->create();
        $otro = User::factory()->create();

        PushSubscription::create([
            'user_id' => $usuario->id,
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/mio',
            'endpoint_hash' => hash('sha256', 'https://fcm.googleapis.com/fcm/send/mio'),
            'public_key' => 'x',
            'auth_token' => 'y',
        ]);
        PushSubscription::create([
            'user_id' => $otro->id,
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/ajeno',
            'endpoint_hash' => hash('sha256', 'https://fcm.googleapis.com/fcm/send/ajeno'),
            'public_key' => 'x',
            'auth_token' => 'y',
        ]);

        $this->actingAs($usuario)->deleteJson(route('movil.push.destroy'), [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/mio',
        ])->assertOk();

        $this->assertDatabaseMissing('push_subscriptions', ['endpoint' => 'https://fcm.googleapis.com/fcm/send/mio']);
        $this->assertDatabaseHas('push_subscriptions', ['endpoint' => 'https://fcm.googleapis.com/fcm/send/ajeno']);
    }

    /**
     * @return array{0: User, 1: User, 2: ChatConversacion, 3: \App\Models\ChatMensaje}
     */
    private function crearConversacionConMensaje(): array
    {
        $emisor = User::factory()->create();
        $destinatario = User::factory()->create();

        $conversacion = ChatConversacion::create(['tipo' => 'privada', 'creado_por' => $emisor->id]);
        $conversacion->participantes()->createMany([
            ['user_id' => $emisor->id],
            ['user_id' => $destinatario->id],
        ]);

        $mensaje = $conversacion->mensajes()->create([
            'user_id' => $emisor->id,
            'cuerpo' => 'Hola, ¿todo bien?',
        ]);
        $mensaje->load('usuario');

        return [$emisor, $destinatario, $conversacion, $mensaje];
    }
}
