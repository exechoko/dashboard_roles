<?php

namespace App\Listeners;

use App\Services\AuditoriaService;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Log;

class AuditLogoutListener
{
    public function handle(Logout $event): void
    {
        try {
            if (is_null($event->user)) {
                return;
            }

            AuditoriaService::registrar(
                accion: 'LOGOUT',
                nombreTabla: 'auth',
                cambios: sprintf('Cierre de sesión: %s (ID %d)', $event->user->email, $event->user->id),
                userId: $event->user->id,
            );
        } catch (\Exception $e) {
            Log::error('AuditLogoutListener: error', ['error' => $e->getMessage()]);
        }
    }
}
