<?php

namespace Database\Factories;

use App\Models\ChatMensaje;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ChatAdjunto>
 */
class ChatAdjuntoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'chat_mensaje_id' => ChatMensaje::factory(),
            'nombre_original' => $this->faker->word() . '.pdf',
            'ruta' => 'chat/' . $this->faker->uuid() . '.pdf',
            'mime' => 'application/pdf',
            'tamano' => $this->faker->numberBetween(1000, 500000),
        ];
    }
}
