<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ClearVaultSession
{
    public function handle(Request $request, Closure $next)
    {
        // Borra el acceso al gestor cuando el usuario navega a cualquier ruta fuera del vault
        if (!$request->is('password-vault*') && !$request->is('password-shares*') && !$request->is('password-vault-auth*')) {
            session()->forget('master_password_verified');
        }

        return $next($request);
    }
}
