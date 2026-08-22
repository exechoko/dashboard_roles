<?php

namespace App\Jobs;

use App\Models\ChatbotMessage;
use App\Models\User;
use App\Services\Chatbot\CatalogoConsultas;
use App\Services\ChatbotContentSanitizer;
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

    public function handle(
        OpenCodeService $openCode,
        CatalogoConsultas $catalogo,
        ChatbotContentSanitizer $contentSanitizer
    ): void {
        $assistantMessage = ChatbotMessage::query()
            ->with('conversation.user')
            ->findOrFail($this->assistantMessageId);

        $assistantMessage->update([
            'status' => 'processing',
            'error_message' => null,
        ]);

        $conversation = $assistantMessage->conversation;
        $usuario = $conversation->user;
        $userMessage = $conversation->messages()
            ->where('role', 'user')
            ->where('id', '<', $assistantMessage->id)
            ->latest('id')
            ->firstOrFail();

        if ($conversation->remote_session_id === null) {
            $session = $openCode->createSession($conversation->title ?? 'Ayuda C.A.R. 911');
            $conversation->update(['remote_session_id' => $session['id']]);
        }

        $permissions = $usuario->getAllPermissions()
            ->pluck('name')
            ->sort()
            ->values()
            ->implode(', ');

        $prompt = "CONTEXTO DEL USUARIO\n"
            . 'Pantalla actual: ' . ($userMessage->context_path ?: 'no informada') . "\n"
            . 'Permisos habilitados: ' . ($permissions !== '' ? $permissions : 'ninguno informado') . "\n\n"
            . "CONSULTA\n{$userMessage->content}";

        $respuesta = $openCode->sendMessage(
            $conversation->remote_session_id,
            $prompt,
            $catalogo->describirPara($usuario)
        );

        $solicitud = $this->consultaSolicitada($respuesta);

        $assistantMessage->update([
            'content' => $solicitud !== null
                ? $this->responderConDatos($catalogo, $usuario, $solicitud['consulta'], $solicitud['parametros'])
                : $contentSanitizer->sanitizeOutput($respuesta),
            'status' => 'completed',
        ]);
    }

    /**
     * Detecta si el modelo pidió ejecutar una consulta de datos en lugar de
     * responder texto. Sólo cuenta si toda la respuesta es ese objeto JSON.
     *
     * @return array{consulta: string, parametros: array<string, mixed>}|null
     */
    protected function consultaSolicitada(string $respuesta): ?array
    {
        $texto = trim($respuesta);
        $texto = preg_replace('/^```(?:json)?\s*(.*?)\s*```$/isu', '$1', $texto) ?? $texto;

        $decodificado = json_decode(trim($texto), true);

        if (!is_array($decodificado) || !is_string($decodificado['consulta'] ?? null)) {
            return null;
        }

        $parametros = $decodificado['parametros'] ?? [];

        return [
            'consulta' => $decodificado['consulta'],
            'parametros' => is_array($parametros) ? $parametros : [],
        ];
    }

    /**
     * Ejecuta la consulta pedida revalidando los permisos del usuario: el
     * modelo elige el nombre, pero el catálogo es la única autoridad.
     *
     * @param  array<string, mixed>  $parametros
     */
    protected function responderConDatos(
        CatalogoConsultas $catalogo,
        User $usuario,
        string $nombre,
        array $parametros
    ): string {
        $consulta = $catalogo->resolver($usuario, $nombre);

        if ($consulta === null) {
            Log::warning('El chatbot pidió una consulta de datos inexistente o sin permiso.', [
                'consulta' => $nombre,
                'user_id' => $usuario->id,
            ]);

            return 'No tengo habilitada ninguna consulta que responda eso con tus permisos actuales.';
        }

        try {
            return $consulta->ejecutar($usuario, $parametros);
        } catch (Throwable $exception) {
            Log::error('Falló una consulta de datos del chatbot.', [
                'consulta' => $nombre,
                'user_id' => $usuario->id,
                'error' => $exception->getMessage(),
            ]);

            return 'No pude obtener ese dato en este momento. Intentá nuevamente en unos minutos.';
        }
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
