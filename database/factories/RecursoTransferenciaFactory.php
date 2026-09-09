<?php

namespace Database\Factories;

use App\Models\Recurso;
use App\Models\RecursoTransferencia;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RecursoTransferencia>
 */
class RecursoTransferenciaFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'recurso_id'               => Recurso::query()->value('id'),
            'vehiculo_id'              => null,
            'destino_transferencia_id' => null,
            'reparticion_texto'        => $this->faker->optional()->company(),
            'fecha_transferencia'      => $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'observaciones'            => $this->faker->optional()->sentence(),
            'estado'                   => RecursoTransferencia::ESTADO_PENDIENTE,
            'user_id_reporte'          => User::factory(),
            'user_id_resolucion'       => null,
            'fecha_resolucion'         => null,
            'motivo_rechazo'           => null,
        ];
    }

    public function confirmada(): static
    {
        return $this->state(fn (array $attributes): array => [
            'estado'             => RecursoTransferencia::ESTADO_CONFIRMADA,
            'user_id_resolucion' => User::factory(),
            'fecha_resolucion'   => now(),
        ]);
    }

    public function rechazada(): static
    {
        return $this->state(fn (array $attributes): array => [
            'estado'             => RecursoTransferencia::ESTADO_RECHAZADA,
            'user_id_resolucion' => User::factory(),
            'fecha_resolucion'   => now(),
            'motivo_rechazo'     => $this->faker->sentence(),
        ]);
    }
}
