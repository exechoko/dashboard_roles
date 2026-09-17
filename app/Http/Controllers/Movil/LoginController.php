<?php

namespace App\Http\Controllers\Movil;

use App\Http\Controllers\Auth\LoginController as BaseLoginController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
     * Además del chequeo de red externa del login de escritorio, la app
     * móvil (PWA) tiene su propio permiso: un usuario puede tener acceso al
     * sistema pero no a la PWA (por ejemplo, si no le corresponde para su
     * puesto).
     */
    protected function authenticated(Request $request, $user)
    {
        if ($response = parent::authenticated($request, $user)) {
            return $response;
        }

        // El super administrador siempre puede acceder
        if ($user->email === 'admin@gmail.com') {
            return;
        }

        if (!$user->acceso_pwa) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('movil.login')->withErrors([
                $this->username() => 'Su cuenta no tiene permiso para acceder a la aplicación móvil (PWA).',
            ]);
        }
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
