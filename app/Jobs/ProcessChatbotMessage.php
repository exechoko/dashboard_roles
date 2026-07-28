<?php

namespace App\Jobs;

use App\Models\ChatbotMessage;
use App\Services\OpenCodeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessChatbotMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 240;

    public function __construct(public int $assistantMessageId)
    {
    }

    public function handle(OpenCodeService $openCode): void
    {
        $assistantMessage = ChatbotMessage::query()
            ->with('conversation.user')
            ->findOrFail($this->assistantMessageId);

        $assistantMessage->update([
            'status' => 'processing',
            'error_message' => null,
        ]);

        $conversation = $assistantMessage->conversation;
        $userMessage = $conversation->messages()
            ->where('role', 'user')
            ->where('id', '<', $assistantMessage->id)
            ->latest('id')
            ->firstOrFail();

        if ($conversation->remote_session_id === null) {
            $session = $openCode->createSession($conversation->title ?? 'Ayuda C.A.R. 911');
            $conversation->update(['remote_session_id' => $session['id']]);
        }

        $permissions = $conversation->user->getAllPermissions()
            ->pluck('name')
            ->sort()
            ->values()
            ->implode(', ');

        $prompt = "CONTEXTO DEL USUARIO\n"
            . 'Pantalla actual: ' . ($userMessage->context_path ?: 'no informada') . "\n"
            . 'Permisos habilitados: ' . ($permissions !== '' ? $permissions : 'ninguno informado') . "\n\n"
            . "CONSULTA\n{$userMessage->content}";

        $answer = $openCode->sendMessage($conversation->remote_session_id, $prompt);

        $assistantMessage->update([
            'content' => $answer,
            'status' => 'completed',
        ]);
    }

    public function failed(Throwable $exception): void
    {
        ChatbotMessage::query()->whereKey($this->assistantMessageId)->update([
            'status' => 'failed',
            'error_message' => 'El asistente no está disponible en este momento. Intentá nuevamente más tarde.',
        ]);

        Log::error('Falló el procesamiento de una consulta del chatbot.', [
            'message_id' => $this->assistantMessageId,
            'error' => $exception->getMessage(),
        ]);
    }
}
