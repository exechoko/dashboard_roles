<?php

namespace App\Console\Commands;

use App\Models\EventoCecoco;
use App\Services\EventoCecocoParser;
use Illuminate\Console\Command;

class LimpiarDireccionesCecoco extends Command
{
    protected $signature = 'cecoco:limpiar-direcciones
                            {--dry-run : Solo mostrar qué cambiaría, sin modificar la base}
                            {--muestra=15 : Cantidad de ejemplos a mostrar}';

    protected $description = 'Aplica a los eventos CECOCO ya importados la misma limpieza de dirección que hace la importación (marcadores D.D/Género y datos de agresor/víctima)';

    public function handle(): int
    {
        $dryRun  = (bool) $this->option('dry-run');
        $muestra = max(0, (int) $this->option('muestra'));

        $candidatas = EventoCecoco::query()
            ->whereNotNull('direccion')
            ->where('direccion', 'REGEXP', 'd[. ]*d|g[eé]nero|violencia|dual|agr|vic|no +cerrar|en +proceso|fiscal')
            ->distinct()
            ->pluck('direccion');

        $this->info($candidatas->count() . ' direcciones distintas candidatas.');

        $filasAfectadas = 0;
        $aNull          = 0;
        $ejemplos       = [];

        foreach ($candidatas as $original) {
            $limpia = EventoCecocoParser::limpiarDireccion($original);
            if ($limpia === $original) {
                continue;
            }

            $consulta = EventoCecoco::query()
                ->where('direccion', $original)
                ->whereRaw('BINARY direccion = ?', [$original]);

            $filas = $dryRun ? $consulta->count() : $consulta->toBase()->update(['direccion' => $limpia]);

            $filasAfectadas += $filas;
            if ($limpia === null) {
                $aNull += $filas;
            }
            if (count($ejemplos) < $muestra) {
                $ejemplos[] = [$original, $limpia ?? 'NULL', $filas];
            }
        }

        if ($ejemplos !== []) {
            $this->table(['Antes', 'Después', 'Filas'], $ejemplos);
        }

        $this->info(($dryRun ? '[dry-run] Cambiarían ' : 'Actualizadas ') . "{$filasAfectadas} filas ({$aNull} quedan en NULL).");

        return self::SUCCESS;
    }
}
