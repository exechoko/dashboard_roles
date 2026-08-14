<?php

namespace Database\Factories;

use App\Models\ChatConversacion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ChatParticipante>
 */
class ChatParticipanteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'chat_conversacion_id' => ChatConversacion::factory(),
            'user_id' => User::factory(),
            'es_admin' => false,
            'ultimo_leido_id' => null,
            'ultimo_leido_at' => null,
        ];
    }
}
