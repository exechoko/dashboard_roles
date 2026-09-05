<?php

namespace App\Services;

use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class WebPushService
{
    private WebPush $webPush;

    public function __construct()
    {
        $this->webPush = new WebPush([
            'VAPID' => [
                'subject' => config('services.webpush.subject'),
                'publicKey' => config('services.webpush.public_key'),
                'privateKey' => config('services.webpush.private_key'),
            ],
        ]);
    }

    /**
     * Manda una notificación a todas las suscripciones activas del usuario
     * (puede tener más de una: celular y escritorio, por ejemplo). El payload
     * se arma por suscripción — vía $payloadPara(PushSubscription): array —
     * porque la URL a abrir depende de la plataforma de cada una (la ficha
     * liviana de /movil/chat o la vista de escritorio). Las suscripciones que
     * el navegador reporta vencidas o inválidas se borran de una.
     *
     * @param callable(PushSubscription): array{title: string, body: string, url: string} $payloadPara
     * @param array<int, string> $plataformasEnLinea Plataformas ('movil'|'escritorio') donde el
     *   usuario está mirando el chat ahora mismo: se les omite el push porque ya lo ven en vivo.
     */
    public function enviarATodasLasSuscripciones(User $user, callable $payloadPara, array $plataformasEnLinea = []): void
    {
        $suscripciones = PushSubscription::where('user_id', $user->id)->get();

        if ($suscripciones->isEmpty()) {
            return;
        }

        $porEnviar = 0;

        foreach ($suscripciones as $suscripcion) {
            if (in_array($suscripcion->plataforma, $plataformasEnLinea, true)) {
                continue;
            }

            $porEnviar++;

            $this->webPush->queueNotification(
                Subscription::create([
                    'endpoint' => $suscripcion->endpoint,
                    'publicKey' => $suscripcion->public_key,
                    'authToken' => $suscripcion->auth_token,
                    'contentEncoding' => $suscripcion->content_encoding ?: 'aes128gcm',
                ]),
                json_encode($payloadPara($suscripcion))
            );
        }

        if ($porEnviar === 0) {
            return;
        }

        foreach ($this->webPush->flush() as $reporte) {
            if ($reporte->isSuccess()) {
                continue;
            }

            if ($reporte->isSubscriptionExpired()) {
                PushSubscription::where('endpoint', $reporte->getEndpoint())->delete();

                continue;
            }

            Log::warning('WebPushService: envío rechazado por el push service', [
                'endpoint' => $reporte->getEndpoint(),
                'reason' => $reporte->getReason(),
                'status' => $reporte->getResponse()?->getStatusCode(),
                'response' => $reporte->getResponseContent(),
            ]);
        }
    }
}
