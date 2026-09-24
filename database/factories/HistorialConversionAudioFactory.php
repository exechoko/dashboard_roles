<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\HistorialConversionAudio>
 */
class HistorialConversionAudioFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'nombre_archivo' => $this->faker->word() . '.wav',
            'extension_original' => 'wav',
            'exito' => true,
            'mensaje_error' => null,
        ];
    }

    public function fallido(): self
    {
        return $this->state(fn (): array => [
            'exito' => false,
            'mensaje_error' => 'No se pudo convertir el archivo.',
        ]);
    }
}
