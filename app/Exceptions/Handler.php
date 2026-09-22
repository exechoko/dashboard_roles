<?php

namespace App\Exceptions;

use App\Services\TelegramService;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Cache;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * Tiempo mínimo (minutos) entre dos alertas de Telegram para la misma
     * excepción (misma clase, archivo y línea), para no saturar el chat
     * cuando un error se repite en un loop o en varias requests seguidas.
     */
    private const MINUTOS_ENTRE_ALERTAS_REPETIDAS = 15;

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            $this->alertarTelegramSiCorresponde($e);
        });
    }

    /**
     * Envía una alerta a Telegram para errores que generarían un 500,
     * solo en producción y con throttling por excepción repetida.
     */
    private function alertarTelegramSiCorresponde(Throwable $e): void
    {
        if (!app()->isProduction()) {
            return;
        }

        $clave = 'telegram_alerta_excepcion_' . md5(get_class($e) . $e->getFile() . $e->getLine());

        if (!Cache::add($clave, true, now()->addMinutes(self::MINUTOS_ENTRE_ALERTAS_REPETIDAS))) {
            return;
        }

        try {
            $mensaje = "🔥 <b>Error 500 en Dashboard</b>\n"
                . '📋 <code>' . get_class($e) . "</code>\n"
                . '📝 ' . mb_substr($e->getMessage(), 0, 300) . "\n"
                . '📍 ' . basename($e->getFile()) . ':' . $e->getLine() . "\n"
                . '🔗 ' . request()?->fullUrl();

            app(TelegramService::class)->enviarMensaje($mensaje);
        } catch (Throwable $errorAlEnviar) {
            // La alerta nunca debe interrumpir el manejo normal de la excepción.
        }
    }
}
