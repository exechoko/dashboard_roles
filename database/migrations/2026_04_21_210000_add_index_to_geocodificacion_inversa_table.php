<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega un índice compuesto (latitud, longitud) a `geocodificacion_inversa`
 * para acelerar los lookups de coordenadas cuando la tabla crece.
 *
 * La tabla funciona como caché persistente del reverse-geocoding: cada
 * coordenada consultada por primera vez se guarda, y las consultas
 * posteriores se resuelven por lookup por lat/lng (ver GeocodificacionService).
 *
 * Nota: las queries actuales usan `ROUND(latitud,5) = ?` que no aprovecha
 * plenamente el índice en MySQL < 8. Igual es útil para BETWEEN-style
 * queries y ordenes espaciales futuras.
 */
return new class extends Migration {
    public function up(): void
    {
        if (!$this->indexExists('geocodificacion_inversa', 'idx_geocod_inv_lat_lng')) {
            Schema::table('geocodificacion_inversa', function (Blueprint $table) {
                $table->index(['latitud', 'longitud'], 'idx_geocod_inv_lat_lng');
            });
        }
    }

    public function down(): void
    {
        if ($this->indexExists('geocodificacion_inversa', 'idx_geocod_inv_lat_lng')) {
            Schema::table('geocodificacion_inversa', function (Blueprint $table) {
                $table->dropIndex('idx_geocod_inv_lat_lng');
            });
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $result = DB::select("
            SELECT COUNT(*) AS total
            FROM information_schema.STATISTICS
            WHERE table_schema = DATABASE()
              AND table_name = ?
              AND index_name = ?
        ", [$table, $indexName]);

        return (int) $result[0]->total > 0;
    }
};
