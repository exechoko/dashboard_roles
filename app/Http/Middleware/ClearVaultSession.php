<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ClearVaultSession
{
    public function handle(Request $request, Closure $next)
    {
        // Borra el acceso al gestor cuando el usuario navega a cualquier ruta fuera del vault.
        // Se ignoran los pedidos AJAX/background (ej. los widgets del dashboard que hacen
        // polling cada 60s): si el usuario dejó el dashboard abierto en otra pestaña, esos
        // pedidos no deben invalidar la sesión de la pestaña donde está usando el vault.
        $esNavegacionFueraDelVault = !$request->ajax()
            && !$request->wantsJson()
            && !$request->is('password-vault*')
            && !$request->is('password-shares*')
            && !$request->is('password-vault-auth*');

        if ($esNavegacionFueraDelVault) {
            session()->forget('master_password_verified');
        }

        return $next($request);
    }
}
