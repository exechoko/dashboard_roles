<?php

namespace Tests\Feature;

use App\Models\PasswordVault;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MovilPasswordVaultTest extends TestCase
{
    use DatabaseTransactions;

    private const CLAVE_MAESTRA = 'clave-maestra-de-prueba-movil';

    public function test_un_usuario_sin_ver_clave_recibe_403(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)->get(route('movil.password-vault.index'))->assertForbidden();
    }

    public function test_con_permiso_pero_sin_clave_maestra_configurada_entra_directo(): void
    {
        $usuario = $this->usuarioConVerClave();

        $this->actingAs($usuario)->get(route('movil.password-vault.index'))->assertOk();
    }

    public function test_con_clave_maestra_configurada_y_sin_desbloquear_redirige_a_pedirla(): void
    {
        $usuario = $this->usuarioConVerClave(Hash::make(self::CLAVE_MAESTRA));

        $this->actingAs($usuario)
            ->get(route('movil.password-vault.index'))
            ->assertRedirect(route('movil.password-vault.master-password'));
    }

    public function test_verificar_la_clave_maestra_desde_movil_da_acceso_y_vuelve_a_la_pagina_que_queria_ver(): void
    {
        $usuario = $this->usuarioConVerClave(Hash::make(self::CLAVE_MAESTRA));

        $this->actingAs($usuario)->get(route('movil.password-vault.index'));

        $this->actingAs($usuario)
            ->post(route('movil.password-vault.verify-master-password'), ['master_password' => self::CLAVE_MAESTRA])
            ->assertRedirect(route('movil.password-vault.index'));
    }

    public function test_una_clave_maestra_incorrecta_no_desbloquea_desde_movil(): void
    {
        $usuario = $this->usuarioConVerClave(Hash::make(self::CLAVE_MAESTRA));

        $this->actingAs($usuario)
            ->post(route('movil.password-vault.verify-master-password'), ['master_password' => 'incorrecta'])
            ->assertSessionHasErrors('master_password');
    }

    public function test_el_listado_muestra_las_propias_y_las_compartidas_pero_no_las_de_otros(): void
    {
        $usuario = $this->usuarioConVerClave();
        $otro = User::factory()->create();

        $propia = PasswordVault::create([
            'user_id' => $usuario->id,
            'system_name' => 'Sistema Propio',
            'system_type' => 'web',
            'username' => 'usuario1',
            'password' => 'secreta1',
        ]);

        $ajena = PasswordVault::create([
            'user_id' => $otro->id,
            'system_name' => 'Sistema Ajeno',
            'system_type' => 'web',
            'username' => 'usuario2',
            'password' => 'secreta2',
        ]);

        $response = $this->actingAs($usuario)->get(route('movil.password-vault.index'));

        $response->assertOk()->assertViewIs('movil.passwords.index');
        $response->assertSee('Sistema Propio');
        $response->assertDontSee('Sistema Ajeno');
    }

    public function test_un_usuario_sin_acceso_a_una_contrasena_recibe_403_al_verla(): void
    {
        $usuario = $this->usuarioConVerClave();
        $otro = User::factory()->create();

        $ajena = PasswordVault::create([
            'user_id' => $otro->id,
            'system_name' => 'Sistema Ajeno',
            'system_type' => 'web',
            'username' => 'usuario2',
            'password' => 'secreta2',
        ]);

        $this->actingAs($usuario)
            ->get(route('movil.password-vault.show', $ajena))
            ->assertForbidden();
    }

    public function test_el_dueño_puede_ver_el_detalle_con_la_contrasena_desencriptada(): void
    {
        $usuario = $this->usuarioConVerClave();

        $propia = PasswordVault::create([
            'user_id' => $usuario->id,
            'system_name' => 'Sistema Propio',
            'system_type' => 'web',
            'username' => 'usuario1',
            'password' => 'secreta1',
        ]);

        $response = $this->actingAs($usuario)->get(route('movil.password-vault.show', $propia));

        $response->assertOk()->assertViewIs('movil.passwords.show');
        $response->assertSee('value="secreta1"', false);
    }

    private function usuarioConVerClave(?string $masterPassword = null): User
    {
        $role = Role::firstOrCreate(['name' => 'rol_test_vault_movil', 'guard_name' => 'web']);
        $role->givePermissionTo(Permission::firstOrCreate(['name' => 'ver-clave', 'guard_name' => 'web']));

        $usuario = User::factory()->create(['master_password' => $masterPassword]);
        $usuario->assignRole($role);

        return $usuario->fresh();
    }
}
