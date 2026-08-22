<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Exige la contraseña maestra para entrar al gestor de contraseñas.
 *
 * El desbloqueo se guarda en la sesión como una marca de tiempo y vence por
 * inactividad. Antes se borraba apenas el usuario pedía cualquier URL fuera del
 * gestor, pero eso incluía los pedidos en segundo plano que dispara la propia
 * página (el widget del chatbot, los widgets del dashboard), así que el vault se
 * bloqueaba solo y volvía a pedir la clave en cada búsqueda o alta.
 */
class VerifyMasterPassword
{
    public const SESSION_KEY = 'master_password_verified_at';

    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (empty($user->master_password)) {
            return $next($request);
        }

        if (static::desbloqueoVigente()) {
            // Mientras el usuario siga trabajando dentro del gestor, se renueva
            // el desbloqueo: sólo vence tras un rato sin usarlo.
            static::marcarDesbloqueado();

            return $next($request);
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'message' => 'La sesión del gestor de contraseñas venció. Volvé a ingresar la contraseña maestra.',
            ], 401);
        }

        session(['master_password_intended' => $request->fullUrl()]);

        return redirect()->route('password-vault.master-password');
    }

    public static function desbloqueoVigente(): bool
    {
        $verificadoEn = session(static::SESSION_KEY);

        if (!is_numeric($verificadoEn)) {
            return false;
        }

        $minutos = (int) config('auth.master_password_timeout', 30);

        return Carbon::createFromTimestamp((int) $verificadoEn)->addMinutes($minutos)->isFuture();
    }

    public static function marcarDesbloqueado(): void
    {
        session([static::SESSION_KEY => Carbon::now()->getTimestamp()]);
    }

    public static function olvidarDesbloqueo(): void
    {
        session()->forget(static::SESSION_KEY);
    }
}
