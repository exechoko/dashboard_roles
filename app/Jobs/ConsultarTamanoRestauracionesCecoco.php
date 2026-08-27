<?php

namespace App\Jobs;

use App\Models\Notificacion;
use App\Services\CecocoExpedienteService;
use App\Services\TelegramService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ConsultarTamanoRestauracionesCecoco implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const CACHE_FLAG_ALERTA = 'cecoco.restauraciones_alerta.';

    private bool $gps;

    public function __construct(bool $gps = false)
    {
        $this->gps = $gps;
    }

    /**
     * Corre tanto desde el schedule horario (Kernel) como desde el botón
     * "refrescar ahora" de la pantalla Workers, así que evaluar el umbral acá
     * cubre ambos disparadores sin duplicar la lógica de alerta.
     */
    public function handle(CecocoExpedienteService $service, TelegramService $telegram): void
    {
        try {
            $resultado = $this->gps
                ? $service->actualizarCacheTamanoBaseRestauracionesGps()
                : $service->actualizarCacheTamanoBaseRestauraciones();

            $this->evaluarUmbral((float) $resultado['mb'], $telegram);
        } catch (\Throwable $e) {
            Log::warning('Job ConsultarTamanoRestauracionesCecoco falló', [
                'gps' => $this->gps,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function evaluarUmbral(float $mb, TelegramService $telegram): void
    {
        $umbral = (float) config('cecoco.umbral_restauraciones_mb');
        $flagKey = self::CACHE_FLAG_ALERTA . ($this->gps ? 'gps' : 'cecoco');
        $superaUmbral = $mb > $umbral;
        $yaEnAlerta = Cache::has($flagKey);

        if ($superaUmbral && !$yaEnAlerta) {
            Cache::put($flagKey, true, now()->addDay());
            $this->notificar($mb, $umbral, Notificacion::TIPO_ALERTA, $telegram);

            return;
        }

        if (!$superaUmbral && $yaEnAlerta) {
            Cache::forget($flagKey);
            $this->notificar($mb, $umbral, Notificacion::TIPO_RECUPERACION, $telegram);
        }
    }

    private function notificar(float $mb, float $umbral, string $tipo, TelegramService $telegram): void
    {
        $esAlerta = $tipo === Notificacion::TIPO_ALERTA;
        $etiqueta = $this->gps ? 'GPS' : 'CECOCO';
        $mbTexto = number_format($mb, 0, ',', '.');
        $umbralTexto = number_format($umbral, 0, ',', '.');

        Notificacion::create([
            'categoria' => Notificacion::CATEGORIA_INFRAESTRUCTURA,
            'tipo' => $tipo,
            'nivel' => $esAlerta ? 'danger' : 'success',
            'titulo' => $esAlerta
                ? "BD restauraciones {$etiqueta}: {$mbTexto} MB"
                : "BD restauraciones {$etiqueta} normalizada",
            'mensaje' => $esAlerta
                ? "Supera el umbral de {$umbralTexto} MB. Conviene depurar históricos."
                : "Bajó del umbral de {$umbralTexto} MB ({$mbTexto} MB actual).",
            'datos' => ['tipo' => 'bd_restauraciones', 'origen' => $etiqueta, 'mb' => $mb, 'umbral_mb' => $umbral],
        ]);

        $mensaje = "🗄 <b>BD restauraciones {$etiqueta}</b>\n"
            . '🕐 ' . now()->format('d/m/Y H:i') . "\n"
            . ($esAlerta
                ? "⚠️ Tamaño actual: {$mbTexto} MB — supera el umbral de {$umbralTexto} MB."
                : "✅ Volvió a la normalidad: {$mbTexto} MB (umbral {$umbralTexto} MB).");

        $this->enviarATodos($telegram, $mensaje);
    }

    private function enviarATodos(TelegramService $telegram, string $mensaje): void
    {
        $chatIds = array_filter(array_map('trim', explode(',', (string) config('infraestructura.telegram_chat_ids'))));

        if (empty($chatIds)) {
            $telegram->enviarMensaje($mensaje);

            return;
        }

        foreach ($chatIds as $chatId) {
            $telegram->enviarMensaje($mensaje, $chatId);
        }
    }
}
