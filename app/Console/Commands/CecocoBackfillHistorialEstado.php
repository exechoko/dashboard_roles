<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Completa la columna historial_estado (agregada por la migración
 * add_historial_estado_to_detalle_expediente_cecoco_table) para las filas de
 * detalle_expediente_cecoco que ya existían antes de esa migración.
 *
 * Corre en lotes chicos (UPDATE ... LIMIT) en vez de un único UPDATE sobre toda
 * la tabla, para no mantener bloqueadas todas las filas durante todo el proceso
 * mientras la app sigue escribiendo (refrescos de detalle, prefetch por lote).
 * Es seguro cortarlo (Ctrl+C) y volver a correrlo: solo toca filas pendientes.
 */
class CecocoBackfillHistorialEstado extends Command
{
    protected $signature = 'cecoco:backfill-historial-estado
                            {--lote=2000 : Filas a actualizar por tanda}
                            {--pausa=100 : Milisegundos de pausa entre tandas}';

    protected $description = 'Completa en lotes la columna historial_estado de detalle_expediente_cecoco a partir del detalle_json ya guardado';

    public function handle(): int
    {
        $lote = max(1, (int) $this->option('lote'));
        $pausaMs = max(0, (int) $this->option('pausa'));

        $pendientes = DB::table('detalle_expediente_cecoco')
            ->whereNull('historial_estado')
            ->whereNotNull('detalle_json')
            ->count();

        if ($pendientes === 0) {
            $this->info('Nada pendiente. Fin.');
            return self::SUCCESS;
        }

        $this->info("Filas pendientes: {$pendientes} | Lote: {$lote} | Pausa: {$pausaMs}ms");

        $totalActualizado = 0;
        $t0 = microtime(true);

        while (true) {
            // COALESCE(..., '') asegura que la fila deja de matchear "historial_estado
            // IS NULL" aunque el JSON no tenga historial.estado, evitando loop infinito.
            $actualizadas = DB::update(<<<'SQL'
                UPDATE detalle_expediente_cecoco
                SET historial_estado = COALESCE(JSON_UNQUOTE(JSON_EXTRACT(detalle_json, '$.historial.estado')), '')
                WHERE historial_estado IS NULL AND detalle_json IS NOT NULL
                LIMIT ?
            SQL, [$lote]);

            if ($actualizadas === 0) {
                break;
            }

            $totalActualizado += $actualizadas;
            $this->line('  ' . $totalActualizado . '/' . $pendientes . ' (' . round(100 * $totalActualizado / $pendientes) . '%)');

            if ($pausaMs > 0) {
                usleep($pausaMs * 1000);
            }
        }

        $segundos = round(microtime(true) - $t0, 1);
        $this->info("Listo: {$totalActualizado} filas actualizadas en {$segundos}s.");

        return self::SUCCESS;
    }
}
