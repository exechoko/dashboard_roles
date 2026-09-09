<?php

namespace App\Console\Commands;

use App\Services\Descargas\DescargaRepositorio;
use Illuminate\Console\Command;

class DesactivarArchivosExpirados extends Command
{
    protected $signature = 'descargas:desactivar-expirados';

    protected $description = 'Desactiva los archivos de descarga que han expirado';

    public function handle(DescargaRepositorio $repositorio): int
    {
        $this->info('Buscando archivos expirados...');

        $cantidad = $repositorio->desactivarExpirados();

        if ($cantidad > 0) {
            $this->info("Se desactivaron {$cantidad} archivo(s) expirado(s).");
        } else {
            $this->info('No hay archivos expirados para desactivar.');
        }

        return Command::SUCCESS;
    }
}
