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
 * Restaura un backup existente en segundo plano (ver GenerarBackupBaseDatos
 * para el motivo: evitar el timeout de Cloudflare en producción). El propio
 * BackupBaseDatosService::restaurar() genera un backup de seguridad de la
 * base actual antes de pisarla.
 */
class RestaurarBackupBaseDatos implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1800;

    public function __construct(
        private readonly string $archivo,
        private readonly ?int $usuarioId = null,
    ) {
        // Ver GenerarBackupBaseDatos: misma cola dedicada, mismo motivo.
        $this->onConnection('backups')->onQueue('backups');
    }

    public function handle(BackupBaseDatosService $backups): void
    {
        try {
            $backupSeguridad = $backups->restaurar($this->archivo);
            $backups->marcarCompletado(['archivo' => $this->archivo, 'backup_seguridad' => $backupSeguridad]);

            AuditoriaService::registrar(
                'ACTUALIZAR',
                'configuracion_sistema_backup',
                "restaurado: {$this->archivo} (respaldo previo: {$backupSeguridad})",
                $this->usuarioId
            );
        } catch (Throwable $e) {
            $backups->marcarError($e->getMessage());
        }
    }

    public function failed(Throwable $e): void
    {
        app(BackupBaseDatosService::class)->marcarError($e->getMessage());
    }
}
