<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $password = Str::random(24);

        $usuario = User::create([
            'name' => 'Admin',
            'email' => 'admin@gmail.com',
            'password' => bcrypt($password)
        ]);

        $this->command?->warn("Contraseña generada para admin@gmail.com (se muestra una sola vez): {$password}");

        /*$rol = Role::create(['name'=>'Super Administrador']);
        $permisos = Permission::pluck('id', 'id')->all();
        $rol->syncPermissions($permisos);
        $usuario->assignRole([$rol->id]);*/
        //Como ya esta creado el rol Administrador es que las lineas anteriores se comentan
        $usuario->assignRole('Super Administrador');
    }
}
