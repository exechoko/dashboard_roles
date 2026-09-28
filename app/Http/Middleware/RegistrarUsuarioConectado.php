<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Registra, en cache, la última actividad de cada usuario autenticado.
 *
 * No hay tabla de sesiones consultable (SESSION_DRIVER=file) ni presencia por
 * websockets fuera del chat, así que el panel de "usuarios conectados" se arma
 * a partir de esta marca de tiempo por usuario. Se guarda en una única entrada
 * de cache (no una por usuario) porque el driver de archivos no permite listar
 * claves, y de cada request se aprovecha para descartar las marcas vencidas.
 */
class RegistrarUsuarioConectado
{
    public const CACHE_KEY = 'usuarios_conectados';

    public const MINUTOS_VIGENCIA = 5;

    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $usuario = Auth::user();
            $conectados = Cache::get(self::CACHE_KEY, []);
            $vencimiento = now()->subMinutes(self::MINUTOS_VIGENCIA);

            $conectados = collect($conectados)
                ->filter(fn (array $datos) => \Illuminate\Support\Carbon::parse($datos['visto_en'])->greaterThan($vencimiento))
                ->all();

            $conectados[$usuario->id] = [
                'visto_en' => now()->toDateTimeString(),
                'ruta' => $request->path(),
            ];

            Cache::put(self::CACHE_KEY, $conectados, now()->addMinutes(self::MINUTOS_VIGENCIA));
        }

        return $next($request);
    }
}
