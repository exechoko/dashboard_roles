<?php

namespace App\Console\Commands;

use App\Models\EventoCecoco;
use App\Models\GeocodificacionDirecta;
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
        $coordsCopiadas = 0;
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
            } elseif ($this->copiarGeocodificacion($original, $limpia, $dryRun)) {
                $coordsCopiadas++;
            }
            if (count($ejemplos) < $muestra) {
                $ejemplos[] = [$original, $limpia ?? 'NULL', $filas];
            }
        }

        if ($ejemplos !== []) {
            $this->table(['Antes', 'Después', 'Filas'], $ejemplos);
        }

        $this->info(($dryRun ? '[dry-run] Cambiarían ' : 'Actualizadas ') . "{$filasAfectadas} filas ({$aNull} quedan en NULL).");
        $this->info(($dryRun ? '[dry-run] Se copiarían ' : 'Copiadas ') . "{$coordsCopiadas} geocodificaciones a la dirección limpia.");

        return self::SUCCESS;
    }

    /**
     * Copia la geocodificación con coordenadas de la dirección original a la limpia
     * (si esta todavía no tiene una), para no volver a geocodificarla.
     */
    private function copiarGeocodificacion(string $original, string $limpia, bool $dryRun): bool
    {
        $existente = GeocodificacionDirecta::query()
            ->where('direccion_original', $original)
            ->whereNotNull('latitud')
            ->whereNotNull('longitud')
            ->first();

        if ($existente === null || GeocodificacionDirecta::query()->where('direccion_original', $limpia)->exists()) {
            return false;
        }

        if (!$dryRun) {
            GeocodificacionDirecta::query()->create([
                'direccion_original'    => $limpia,
                'direccion_normalizada' => $existente->direccion_normalizada,
                'latitud'               => $existente->latitud,
                'longitud'              => $existente->longitud,
                'fuente'                => $existente->fuente,
                'nro_expediente'        => $existente->nro_expediente,
            ]);
        }

        return true;
    }
}
