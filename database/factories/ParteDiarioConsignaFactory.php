<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ParteDiarioConsigna>
 */
class ParteDiarioConsignaFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'grupo'  => $this->faker->optional()->randomElement(['Microcentro', 'Costanera']),
            'nombre' => $this->faker->unique()->words(2, true),
            'orden'  => $this->faker->numberBetween(0, 50),
            'activa' => true,
        ];
    }

    public function inactiva(): static
    {
        return $this->state(fn (array $attributes): array => ['activa' => false]);
    }
}
