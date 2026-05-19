<?php

namespace App\Console\Commands;

use App\Services\EfemeridesService;
use Illuminate\Console\Command;

class ActualizarEfemerides extends Command
{
    protected $signature = 'efemerides:actualizar';

    protected $description = 'Consulta la API de Wikipedia y refresca el caché diario de efemérides (Argentina / Entre Ríos).';

    public function handle(EfemeridesService $efemerides): int
    {
        $this->info('Actualizando efemérides del día…');
        $resultado = $efemerides->refrescar();

        $this->info(sprintf(
            'Listo. Argentina: %d eventos · Entre Ríos: %d eventos (fecha %s).',
            count($resultado['argentina']),
            count($resultado['entre_rios']),
            $resultado['fecha']
        ));

        return self::SUCCESS;
    }
}
