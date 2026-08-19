<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatEscribiendo implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public int $conversacionId, public int $usuarioId)
    {
    }

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel("chat.conversacion.{$this->conversacionId}")];
    }

    public function broadcastAs(): string
    {
        return 'chat.escribiendo';
    }

    /**
     * @return array<string, int>
     */
    public function broadcastWith(): array
    {
        return ['usuario_id' => $this->usuarioId];
    }
}
