<?php

namespace App\Services;

use App\Models\PushSubscription;
use App\Models\User;
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
     * Manda el mismo payload a todas las suscripciones activas del usuario
     * (puede tener más de una: distintos celulares/navegadores). Las que el
     * navegador reporta vencidas o inválidas se borran de una.
     *
     * @param array{title: string, body: string, url: string} $payload
     */
    public function enviarATodasLasSuscripciones(User $user, array $payload): void
    {
        $suscripciones = PushSubscription::where('user_id', $user->id)->get();

        if ($suscripciones->isEmpty()) {
            return;
        }

        $cuerpo = json_encode($payload);

        foreach ($suscripciones as $suscripcion) {
            $this->webPush->queueNotification(
                Subscription::create([
                    'endpoint' => $suscripcion->endpoint,
                    'publicKey' => $suscripcion->public_key,
                    'authToken' => $suscripcion->auth_token,
                    'contentEncoding' => $suscripcion->content_encoding ?: 'aes128gcm',
                ]),
                $cuerpo
            );
        }

        foreach ($this->webPush->flush() as $reporte) {
            if ($reporte->isSuccess()) {
                continue;
            }

            if ($reporte->isSubscriptionExpired()) {
                PushSubscription::where('endpoint', $reporte->getEndpoint())->delete();
            }
        }
    }
}
