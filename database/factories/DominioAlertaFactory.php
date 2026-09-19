<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DominioAlerta>
 */
class DominioAlertaFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'dominio' => strtoupper($this->faker->bothify('??###??')),
            'marca' => $this->faker->randomElement(['FIAT', 'VW', 'FORD', 'CHEVROLET']),
            'modelo' => $this->faker->word(),
            'color' => $this->faker->safeColorName(),
            'motivo' => $this->faker->sentence(),
            'solicitado_por' => $this->faker->name(),
            'funcionario_carga' => $this->faker->name(),
            'notificar_a' => $this->faker->name(),
            'fecha_carga' => now()->toDateString(),
            'activo' => true,
        ];
    }
}
