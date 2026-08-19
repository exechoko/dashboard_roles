<?php

namespace App\Events;

use App\Models\ChatConversacion;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatConversacionCreada implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ChatConversacion $conversacion)
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
        return 'chat.conversacion.creada';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->conversacion->id,
            'tipo' => $this->conversacion->tipo,
            'nombre' => $this->conversacion->nombre,
            'actualizado_en' => $this->conversacion->updated_at?->toIso8601String(),
            'participantes' => $this->conversacion->usuarios()
                ->get()
                ->map(fn (User $usuario): array => [
                    'id' => $usuario->id,
                    'nombre' => trim($usuario->name . ' ' . $usuario->apellido),
                ])
                ->values()
                ->all(),
        ];
    }
}
