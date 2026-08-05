<?php

namespace App\Console\Commands;

use App\Services\DetectorActivacionesTotem;
use Illuminate\Console\Command;

/**
 * Detecta en los eventos CECOCO de los últimos N días las descripciones que
 * indican una activación de tótem BDE, y las registra en activaciones_totem
 * para su seguimiento. Pensado para correr después del import diario de
 * CECOCO; también se dispara a demanda desde el botón "Escanear ahora".
 */
class DetectarActivacionesTotem extends Command
{
    protected $signature = 'totem:detectar-activaciones {--dias=7 : Cantidad de días hacia atrás a escanear}';

    protected $description = 'Detecta activaciones de tótem BDE en los eventos CECOCO recientes';

    public function handle(DetectorActivacionesTotem $detector): int
    {
        $dias = max(1, (int) $this->option('dias'));

        $this->line('========================================');
        $this->line('[' . now()->format('Y-m-d H:i:s') . '] totem:detectar-activaciones iniciado');
        $this->info("Ventana: últimos {$dias} días");

        $creadas = $detector->detectar($dias);

        $this->info("Activaciones nuevas registradas: {$creadas}");
        $this->line('========================================');

        return self::SUCCESS;
    }
}
