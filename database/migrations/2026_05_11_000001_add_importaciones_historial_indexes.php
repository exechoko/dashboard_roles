<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!$this->indexExists('importaciones', 'idx_importaciones_created_at')) {
            DB::statement('CREATE INDEX idx_importaciones_created_at ON importaciones (created_at)');
        }

        if (!$this->indexExists('importaciones', 'idx_importaciones_estado_anio')) {
            DB::statement('CREATE INDEX idx_importaciones_estado_anio ON importaciones (estado, anio)');
        }
    }

    public function down(): void
    {
        if ($this->indexExists('importaciones', 'idx_importaciones_estado_anio')) {
            DB::statement('DROP INDEX idx_importaciones_estado_anio ON importaciones');
        }

        if ($this->indexExists('importaciones', 'idx_importaciones_created_at')) {
            DB::statement('DROP INDEX idx_importaciones_created_at ON importaciones');
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        $database = DB::getDatabaseName();
        $result = DB::select(
            'SELECT COUNT(1) as total FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$database, $table, $index]
        );

        return (int) ($result[0]->total ?? 0) > 0;
    }
};
