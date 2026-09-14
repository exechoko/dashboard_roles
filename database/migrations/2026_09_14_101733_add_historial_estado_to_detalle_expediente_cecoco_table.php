<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('detalle_expediente_cecoco', function (Blueprint $table) {
            // Copia denormalizada de detalle_json->historial->estado. La vista de
            // "expedientes que siguen abiertos" (importar/form) necesitaba filtrar
            // por ese valor con JSON_EXTRACT en la tabla completa sin índice posible,
            // tardando varios segundos por carga; esta columna se mantiene en
            // sync al guardar el detalle (ver CecocoExpedienteService) y permite
            // filtrar indexado.
            //
            // El backfill de filas existentes NO va acá: correr un UPDATE sin LIMIT
            // sobre toda la tabla la bloquearía por el tiempo completo del backfill
            // (mientras la app sigue escribiendo detalles). Se hace aparte, en lotes,
            // con `php artisan cecoco:backfill-historial-estado`.
            $table->string('historial_estado', 60)->nullable()->after('detalle_json');
            $table->index('historial_estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('detalle_expediente_cecoco', function (Blueprint $table) {
            $table->dropIndex(['historial_estado']);
            $table->dropColumn('historial_estado');
        });
    }
};
