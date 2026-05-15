<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VerifyMasterPassword
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (empty($user->master_password)) {
            return $next($request);
        }

        if (session('master_password_verified') === true) {
            return $next($request);
        }

        session(['master_password_intended' => $request->fullUrl()]);

        return redirect()->route('password-vault.master-password');
    }
}
