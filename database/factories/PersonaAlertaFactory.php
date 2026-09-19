<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PersonaAlerta>
 */
class PersonaAlertaFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'dni' => (string) $this->faker->unique()->numberBetween(1000000, 45000000),
            'apellido_nombre' => $this->faker->lastName() . ' ' . $this->faker->firstName(),
            'direccion' => $this->faker->address(),
            'motivo' => $this->faker->sentence(),
            'solicitado_por' => $this->faker->name(),
            'funcionario_carga' => $this->faker->name(),
            'notificar_a' => $this->faker->name(),
            'identificado' => false,
            'finalizado' => false,
            'fecha_carga' => now()->toDateString(),
            'activo' => true,
        ];
    }
}
