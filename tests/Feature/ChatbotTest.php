<?php

namespace Tests\Feature;

use App\Jobs\ProcessChatbotMessage;
use App\Models\ChatbotConversation;
use App\Models\ChatbotMessage;
use App\Models\User;
use App\Services\OpenCodeService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ChatbotTest extends TestCase
{
    use DatabaseTransactions;

    public function test_un_visitante_no_puede_consultar_el_chatbot(): void
    {
        $this->getJson(route('chatbot.history'))->assertUnauthorized();
        $this->postJson(route('chatbot.ask'), ['question' => 'Ayuda'])->assertUnauthorized();
    }

    public function test_un_usuario_puede_encolar_una_consulta(): void
    {
        Queue::fake();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('chatbot.ask'), [
            'question' => '¿Cómo ingreso al módulo de equipos?',
            'context_path' => '/home',
        ]);

        $response->assertAccepted()
            ->assertJsonPath('message.role', 'assistant')
            ->assertJsonPath('message.status', 'pending');

        $this->assertDatabaseHas('chatbot_conversations', ['user_id' => $user->id]);
        $this->assertDatabaseHas('chatbot_messages', [
            'role' => 'user',
            'content' => '¿Cómo ingreso al módulo de equipos?',
            'context_path' => '/home',
        ]);

        Queue::assertPushed(ProcessChatbotMessage::class);
    }

    public function test_valida_la_pregunta_y_la_ruta_de_contexto(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('chatbot.ask'), [
            'question' => '',
            'context_path' => 'https://sitio-externo.test',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['question', 'context_path']);
    }

    public function test_oculta_credenciales_antes_de_guardar_la_consulta(): void
    {
        Queue::fake();
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('chatbot.ask'), [
            'question' => 'Mi token=token-123456789012345, ¿cómo ingreso?',
            'context_path' => '/home',
        ])->assertAccepted();

        $message = ChatbotMessage::query()->where('role', 'user')->latest('id')->firstOrFail();

        $this->assertStringNotContainsString('token-123456789012345', $message->content);
        $this->assertStringContainsString('[CREDENCIAL OCULTA]', $message->content);
    }

    public function test_un_usuario_no_puede_consultar_un_mensaje_ajeno(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $conversation = ChatbotConversation::create([
            'user_id' => $owner->id,
            'title' => 'Consulta privada',
        ]);
        $message = $conversation->messages()->create([
            'role' => 'assistant',
            'content' => 'Respuesta',
            'status' => 'completed',
        ]);

        $this->actingAs($otherUser)
            ->getJson(route('chatbot.status', $message))
            ->assertNotFound();
    }

    public function test_el_historial_solo_devuelve_los_mensajes_del_usuario(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $conversation = ChatbotConversation::create(['user_id' => $user->id, 'title' => 'Propia']);
        $otherConversation = ChatbotConversation::create(['user_id' => $otherUser->id, 'title' => 'Ajena']);

        ChatbotMessage::create([
            'chatbot_conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => 'Mensaje propio',
            'status' => 'completed',
        ]);
        ChatbotMessage::create([
            'chatbot_conversation_id' => $otherConversation->id,
            'role' => 'user',
            'content' => 'Mensaje ajeno',
            'status' => 'completed',
        ]);

        $this->actingAs($user)
            ->getJson(route('chatbot.history'))
            ->assertOk()
            ->assertJsonCount(1, 'messages')
            ->assertJsonPath('messages.0.content', 'Mensaje propio');
    }

    public function test_el_job_guarda_la_respuesta_y_la_sesion_remota(): void
    {
        $user = User::factory()->create();
        $conversation = ChatbotConversation::create(['user_id' => $user->id, 'title' => 'Ayuda']);
        $conversation->messages()->create([
            'role' => 'user',
            'content' => '¿Dónde están los equipos?',
            'status' => 'completed',
            'context_path' => '/home',
        ]);
        $assistantMessage = $conversation->messages()->create([
            'role' => 'assistant',
            'status' => 'pending',
        ]);

        $openCode = $this->mock(OpenCodeService::class);
        $openCode->expects('createSession')->once()->andReturn(['id' => 'ses_test']);
        $openCode->expects('sendMessage')
            ->once()
            ->withArgs(fn (string $sessionId, string $prompt): bool => $sessionId === 'ses_test'
                && str_contains($prompt, '¿Dónde están los equipos?'))
            ->andReturn('Ingresá al módulo Equipos.');

        (new ProcessChatbotMessage($assistantMessage->id))->handle($openCode);

        $this->assertSame('ses_test', $conversation->fresh()->remote_session_id);
        $this->assertSame('completed', $assistantMessage->fresh()->status);
        $this->assertSame('Ingresá al módulo Equipos.', $assistantMessage->fresh()->content);
    }
}
