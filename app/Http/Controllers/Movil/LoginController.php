<?php

namespace App\Http\Controllers\Movil;

use App\Http\Controllers\Auth\LoginController as BaseLoginController;
use Illuminate\Http\Request;

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

    /**
     * Al cerrar sesión desde /movil/logout (el botón de la app móvil), volver
     * al login propio de la app móvil en vez del login de escritorio (que es
     * adónde manda por defecto AuthenticatesUsers::logout() al pegarle a la
     * ruta compartida /logout).
     */
    protected function loggedOut(Request $request)
    {
        return redirect()->route('movil.login');
    }
}
