<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ChatConversacion>
 */
class ChatConversacionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tipo' => 'privada',
            'nombre' => null,
            'creado_por' => User::factory(),
        ];
    }

    public function grupo(): self
    {
        return $this->state(fn (array $attributes): array => [
            'tipo' => 'grupo',
            'nombre' => $this->faker->words(3, true),
        ]);
    }
}
