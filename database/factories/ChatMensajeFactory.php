<?php

namespace Database\Factories;

use App\Models\ChatConversacion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ChatMensaje>
 */
class ChatMensajeFactory extends Factory
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
            'cuerpo' => $this->faker->sentence(),
        ];
    }
}
