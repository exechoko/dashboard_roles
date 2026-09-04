<?php

namespace App\Http\Controllers\Movil;

use App\Http\Controllers\Auth\LoginController as BaseLoginController;

class LoginController extends BaseLoginController
{
    /**
     * Adónde ir después de loguearse desde /movil/ingresar cuando no hay una
     * URL "intended" guardada en sesión (p. ej. se entró directo al login,
     * sin haber sido redirigido desde otra página de /movil).
     */
    protected $redirectTo = '/movil';

    public function showLoginForm()
    {
        return view('movil.login');
    }
}
