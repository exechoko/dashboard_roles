<?php

namespace App\Console\Commands;

use App\Services\AuditoriaService;
use App\Services\BackupBaseDatosService;
use Illuminate\Console\Command;
use Throwable;

class BackupBaseDatos extends Command
{
    /**
     * @var string
     */
    protected $signature = 'configuracion:backup-bd {--nota= : Nota opcional para identificar el backup}';

    /**
     * @var string
     */
    protected $description = 'Genera un backup de la base de datos principal (equipamiento) en storage/app/backups/db';

    public function handle(BackupBaseDatosService $service): int
    {
        try {
            $archivo = $service->crear($this->option('nota') ?: 'Backup programado');
        } catch (Throwable $e) {
            $this->error('No se pudo generar el backup: ' . $e->getMessage());

            return self::FAILURE;
        }

        AuditoriaService::registrar('CREAR', 'configuracion_sistema_backup', "archivo: {$archivo}");
        $this->info("Backup generado: {$archivo}");

        return self::SUCCESS;
    }
}
