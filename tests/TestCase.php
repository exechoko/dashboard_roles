<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Spatie\Permission\Models\Role;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Crea un usuario con el rol "Super Administrador", que tiene todos los
     * permisos vía Gate::before.
     *
     * @param  array<string, mixed>  $atributos
     */
    protected function crearSuperAdministrador(array $atributos = []): User
    {
        $usuario = User::factory()->create($atributos);
        $usuario->assignRole(Role::findOrCreate('Super Administrador', 'web'));

        return $usuario;
    }
}
