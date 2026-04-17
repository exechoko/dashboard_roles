<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    /**
     * Called after a successful login. Checks if the user is allowed
     * to access from the public domain.
     */
    protected function authenticated(Request $request, $user)
    {
        // El super administrador siempre puede acceder
        if ($user->email === 'admin@gmail.com') {
            return;
        }

        if ($this->isPublicDomain($request) && !$user->acceso_externo) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                $this->username() => 'Su cuenta no tiene permiso para acceder desde la red externa.',
            ]);
        }
    }

    /**
     * Determines if the current request is coming from the public domain.
     */
    private function isPublicDomain(Request $request): bool
    {
        return $request->getHost() === 'car911.stper.com.ar';
    }
}
