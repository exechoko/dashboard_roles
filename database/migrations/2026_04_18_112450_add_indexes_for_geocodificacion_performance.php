<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // evento_cecoco.direccion — clave para el JOIN de geocodificación
        if (!$this->indexExists('evento_cecoco', 'idx_evento_cecoco_direccion')) {
            DB::statement('CREATE INDEX idx_evento_cecoco_direccion ON evento_cecoco (direccion(191))');
        }

        // geocodificacion_directa.nro_expediente — para el WHERE nro_expediente IS NULL
        if (!$this->indexExists('geocodificacion_directa', 'idx_geocod_directa_nro_exp')) {
            Schema::table('geocodificacion_directa', function (Blueprint $table) {
                $table->index('nro_expediente', 'idx_geocod_directa_nro_exp');
            });
        }
    }

    public function down(): void
    {
        if ($this->indexExists('evento_cecoco', 'idx_evento_cecoco_direccion')) {
            DB::statement('DROP INDEX idx_evento_cecoco_direccion ON evento_cecoco');
        }

        if ($this->indexExists('geocodificacion_directa', 'idx_geocod_directa_nro_exp')) {
            Schema::table('geocodificacion_directa', function (Blueprint $table) {
                $table->dropIndex('idx_geocod_directa_nro_exp');
            });
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $result = DB::select("
            SELECT COUNT(*) as total
            FROM information_schema.STATISTICS
            WHERE table_schema = DATABASE()
              AND table_name = ?
              AND index_name = ?
        ", [$table, $indexName]);

        return $result[0]->total > 0;
    }
};
