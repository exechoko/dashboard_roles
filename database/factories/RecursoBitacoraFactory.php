<?php

namespace Database\Factories;

use App\Models\Recurso;
use App\Models\RecursoBitacora;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RecursoBitacora>
 */
class RecursoBitacoraFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'recurso_id'  => Recurso::query()->value('id'),
            'fecha_hora'  => $this->faker->dateTimeBetween('-6 months', 'now'),
            'categoria'   => $this->faker->randomElement(array_keys(RecursoBitacora::CATEGORIAS)),
            'descripcion' => $this->faker->sentence(),
            'estado'      => null,
            'km'          => $this->faker->optional()->numberBetween(1000, 250000),
            'taller'      => $this->faker->optional()->company(),
            'costo'       => $this->faker->optional()->randomFloat(2, 1000, 500000),
            'user_id'     => User::factory(),
        ];
    }

    public function abierta(): static
    {
        return $this->state(fn (array $attributes): array => ['estado' => RecursoBitacora::ESTADO_ABIERTO]);
    }

    public function cerrada(): static
    {
        return $this->state(fn (array $attributes): array => [
            'estado'      => RecursoBitacora::ESTADO_CERRADO,
            'cerrada_en'  => now(),
            'cerrada_por' => User::factory(),
        ]);
    }
}
