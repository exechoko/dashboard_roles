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
     * Manda una notificación a todas las suscripciones activas del usuario
     * (puede tener más de una: celular y escritorio, por ejemplo). El payload
     * se arma por suscripción — vía $payloadPara(PushSubscription): array —
     * porque la URL a abrir depende de la plataforma de cada una (la ficha
     * liviana de /movil/chat o la vista de escritorio). Las suscripciones que
     * el navegador reporta vencidas o inválidas se borran de una.
     *
     * @param callable(PushSubscription): array{title: string, body: string, url: string} $payloadPara
     */
    public function enviarATodasLasSuscripciones(User $user, callable $payloadPara): void
    {
        $suscripciones = PushSubscription::where('user_id', $user->id)->get();

        if ($suscripciones->isEmpty()) {
            return;
        }

        foreach ($suscripciones as $suscripcion) {
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
