<?php

namespace App\Listeners;

use App\Events\ChatMensajeEnviado;
use App\Models\User;
use App\Services\WebPushService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EnviarPushMensajeChat implements ShouldQueue
{
    public function __construct(private WebPushService $webPush)
    {
    }

    public function handle(ChatMensajeEnviado $event): void
    {
        $mensaje = $event->mensaje;
        $conversacion = $event->conversacion;

        $titulo = trim($mensaje->usuario->name . ' ' . $mensaje->usuario->apellido);
        $cuerpo = $mensaje->cuerpo
            ? Str::limit($mensaje->cuerpo, 120)
            : 'Envió un adjunto';

        $payload = [
            'title' => $titulo,
            'body' => $cuerpo,
            'url' => url("/movil/chat/{$conversacion->id}"),
        ];

        $destinatarios = $conversacion->participantes()
            ->where('user_id', '!=', $mensaje->user_id)
            ->pluck('user_id');

        foreach ($destinatarios as $userId) {
            // Si está activo en el chat ahora mismo (pisó /chat/sync hace
            // menos de 90s), ya lo va a ver en vivo: no duplicar con push.
            if (Cache::has("chat.online.{$userId}")) {
                continue;
            }

            $usuario = User::find($userId);

            if ($usuario === null) {
                continue;
            }

            try {
                $this->webPush->enviarATodasLasSuscripciones($usuario, $payload);
            } catch (\Throwable $e) {
                Log::error('EnviarPushMensajeChat: error al enviar', [
                    'user_id' => $userId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
