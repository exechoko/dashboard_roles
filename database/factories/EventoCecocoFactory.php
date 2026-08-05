<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EventoCecoco>
 */
class EventoCecocoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $fecha = $this->faker->dateTimeBetween('-6 days', 'now');

        return [
            'nro_expediente' => (string) $this->faker->unique()->numberBetween(9000000, 9999999),
            'fecha_hora' => $fecha,
            'operador' => $this->faker->name(),
            'descripcion' => $this->faker->sentence(),
            'direccion' => $this->faker->streetAddress(),
            'tipo_servicio' => 'AVISO',
            'periodo' => $fecha->format('Ym'),
            'anio' => (int) $fecha->format('Y'),
            'mes' => (int) $fecha->format('n'),
        ];
    }
}
