<?php

namespace App\Jobs;

use App\Services\AuditoriaService;
use App\Services\BackupBaseDatosService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Genera un backup de la base `equipamiento` en segundo plano: mysqldump de
 * una base grande puede tardar varios minutos, y correrlo dentro del request
 * HTTP se cortaría atrás de Cloudflare (~100s) en producción. El resultado
 * (o el error) queda en BackupBaseDatosService::estado() para que la
 * pantalla de Backups lo consulte por polling.
 */
class GenerarBackupBaseDatos implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Debe superar el timeout interno del proceso mysqldump (ver
     * BackupBaseDatosService::TIMEOUT_SEGUNDOS) para que el worker no mate
     * el job antes de que el propio proceso corte por su cuenta.
     */
    public int $timeout = 1800;

    public function __construct(
        private readonly ?string $nota = null,
        private readonly ?int $usuarioId = null,
    ) {
        // Cola dedicada con retry_after largo (ver config/queue.php): la de
        // 'database' (90s) re-entregaría este job a otro worker antes de que
        // termine. Requiere un worker propio en producción, igual que 'mbox':
        // php artisan queue:work backups --queue=backups
        $this->onConnection('backups')->onQueue('backups');
    }

    public function handle(BackupBaseDatosService $backups): void
    {
        try {
            $archivo = $backups->crear($this->nota);
            $backups->marcarCompletado(['archivo' => $archivo]);

            AuditoriaService::registrar('CREAR', 'configuracion_sistema_backup', "archivo: {$archivo}", $this->usuarioId);
        } catch (Throwable $e) {
            $backups->marcarError($e->getMessage());
        }
    }

    public function failed(Throwable $e): void
    {
        app(BackupBaseDatosService::class)->marcarError($e->getMessage());
    }
}
