<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\HistorialHashArchivo>
 */
class HistorialHashArchivoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $contenido = $this->faker->text();

        return [
            'user_id' => User::factory(),
            'nombre_archivo' => $this->faker->word() . '.bin',
            'cifrado_aplicado' => 'SHA-256',
            'hash' => hash('sha256', $contenido),
        ];
    }
}
