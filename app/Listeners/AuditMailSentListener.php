<?php

namespace App\Listeners;

use App\Services\AuditoriaService;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Log;

class AuditMailSentListener
{
    public function handle(MessageSent $event): void
    {
        try {
            $destinatarios = collect($event->message->getTo())
                ->map(fn ($direccion) => $direccion->getAddress())
                ->implode(', ');

            AuditoriaService::registrar(
                accion: 'MAIL_ENVIADO',
                nombreTabla: 'mail',
                cambios: sprintf(
                    'Para: %s | Asunto: %s',
                    $destinatarios ?: 'S/D',
                    $event->message->getSubject() ?? 'S/D'
                ),
            );
        } catch (\Exception $e) {
            Log::error('AuditMailSentListener: error', ['error' => $e->getMessage()]);
        }
    }
}
