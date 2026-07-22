<?php

namespace App\Listeners;

use App\Services\AuditoriaService;
use Illuminate\Auth\Events\Failed;
use Illuminate\Support\Facades\Log;

class AuditFailedLoginListener
{
    public function handle(Failed $event): void
    {
        try {
            $email = $event->credentials['email'] ?? $event->credentials['lp'] ?? 'desconocido';

            AuditoriaService::registrar(
                accion: 'LOGIN_FALLIDO',
                nombreTabla: 'auth',
                cambios: sprintf('Intento fallido de inicio de sesión con: %s', $email),
                userId: $event->user?->id,
            );
        } catch (\Exception $e) {
            Log::error('AuditFailedLoginListener: error', ['error' => $e->getMessage()]);
        }
    }
}
