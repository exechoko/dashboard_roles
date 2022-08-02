<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

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
        $usuario = User::create([
            'name' => 'Ernesto',
            'email' => 'ernesto@gmail.com',
            'password' => bcrypt('123456789')
        ]);

        /*$rol = Role::create(['name'=>'Administrador']);
        $permisos = Permission::pluck('id', 'id')->all();
        $rol->syncPermissions($permisos);
        $usuario->assignRole([$rol->id]);*/
        //Como ya esta creado el rol Administrador es que las lineas anteriores se comentan
        $usuario->assignRole('Administrador');
    }
}
