<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyMasterPassword;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PasswordVaultDesbloqueoTest extends TestCase
{
    use DatabaseTransactions;

    private const CLAVE_MAESTRA = 'clave-maestra-de-prueba';

    private function usuarioConVault(): User
    {
        $role = Role::firstOrCreate(['name' => 'rol_test_vault', 'guard_name' => 'web']);

        foreach (['ver-clave', 'crear-clave'] as $nombre) {
            $role->givePermissionTo(Permission::firstOrCreate(['name' => $nombre, 'guard_name' => 'web']));
        }

        $user = User::factory()->create(['master_password' => Hash::make(self::CLAVE_MAESTRA)]);
        $user->assignRole($role);

        return $user;
    }

    public function test_sin_desbloquear_redirige_a_pedir_la_clave_maestra(): void
    {
        $this->actingAs($this->usuarioConVault())
            ->get(route('password-vault.index'))
            ->assertRedirect(route('password-vault.master-password'));
    }

    public function test_verificar_la_clave_maestra_da_acceso_al_gestor(): void
    {
        $this->actingAs($this->usuarioConVault())
            ->post(route('password-vault.verify-master-password'), ['master_password' => self::CLAVE_MAESTRA])
            ->assertRedirect(route('password-vault.index'));

        $this->assertTrue(VerifyMasterPassword::desbloqueoVigente());
    }

    public function test_una_clave_maestra_incorrecta_no_desbloquea(): void
    {
        $this->actingAs($this->usuarioConVault())
            ->post(route('password-vault.verify-master-password'), ['master_password' => 'incorrecta'])
            ->assertSessionHasErrors('master_password');

        $this->assertFalse(VerifyMasterPassword::desbloqueoVigente());
    }

    /**
     * Es la regresión que rompía el gestor: los pedidos en segundo plano que
     * dispara cualquier pantalla (el widget del chatbot, los widgets del
     * dashboard) apagaban el desbloqueo y el vault volvía a pedir la clave en
     * cada búsqueda o alta.
     */
    public function test_pedir_otra_ruta_de_la_app_no_bloquea_el_gestor(): void
    {
        $usuario = $this->usuarioConVault();

        $this->actingAs($usuario)
            ->post(route('password-vault.verify-master-password'), ['master_password' => self::CLAVE_MAESTRA]);

        $this->actingAs($usuario)->get(route('chatbot.history'))->assertOk();

        $this->actingAs($usuario)
            ->get(route('password-vault.index', ['search' => 'algo']))
            ->assertOk();
    }

    public function test_el_desbloqueo_vence_por_inactividad(): void
    {
        $usuario = $this->usuarioConVault();

        $this->actingAs($usuario)
            ->post(route('password-vault.verify-master-password'), ['master_password' => self::CLAVE_MAESTRA]);

        $this->travel((int) config('auth.master_password_timeout') + 1)->minutes();

        $this->actingAs($usuario)
            ->get(route('password-vault.index'))
            ->assertRedirect(route('password-vault.master-password'));

        Carbon::setTestNow();
    }
}
