<?php

namespace App\Events;

use App\Models\ChatConversacion;
use App\Models\ChatMensaje;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatMensajeEnviado implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ChatMensaje $mensaje, public ChatConversacion $conversacion)
    {
    }

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return $this->conversacion->participantes()
            ->pluck('user_id')
            ->map(fn (int $id): PrivateChannel => new PrivateChannel("chat.usuario.{$id}"))
            ->values()
            ->all();
    }

    public function broadcastAs(): string
    {
        return 'chat.mensaje';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'mensaje' => $this->mensaje->paraChat(),
            'conversacion_id' => $this->conversacion->id,
        ];
    }
}
