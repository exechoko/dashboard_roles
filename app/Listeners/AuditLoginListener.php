<?php

namespace App\Listeners;

use App\Services\AuditoriaService;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Log;

class AuditLoginListener
{
    public function handle(Login $event): void
    {
        try {
            AuditoriaService::registrar(
                accion: 'LOGIN',
                nombreTabla: 'auth',
                cambios: sprintf('Inicio de sesión: %s (ID %d)', $event->user->email, $event->user->id),
                userId: $event->user->id,
            );
        } catch (\Exception $e) {
            Log::error('AuditLoginListener: error', ['error' => $e->getMessage()]);
        }
    }
}
