<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPatrimonioFieldsToFlotaGeneralTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('flota_general', function (Blueprint $table) {
            $table->boolean('patrimoniado')->default(false)->after('observaciones');
            $table->unsignedBigInteger('destino_patrimonial_id')->nullable()->after('patrimoniado');
            $table->unsignedBigInteger('cargo_id')->nullable()->after('destino_patrimonial_id');
            $table->date('fecha_patrimonio')->nullable()->after('cargo_id');

            // FK al destino patrimonial
            $table->foreign('destino_patrimonial_id')
                ->references('id')
                ->on('destino')
                ->onDelete('set null');

            // FK al cargo vigente
            $table->foreign('cargo_id')
                ->references('id')
                ->on('patrimonio_cargos')
                ->onDelete('set null');

            // Índice para filtros rápidos
            $table->index('patrimoniado', 'idx_flota_patrimoniado');
            $table->index('destino_patrimonial_id', 'idx_flota_destino_patrimonial');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('flota_general', function (Blueprint $table) {
            $table->dropForeign(['destino_patrimonial_id']);
            $table->dropForeign(['cargo_id']);
            $table->dropIndex('idx_flota_patrimoniado');
            $table->dropIndex('idx_flota_destino_patrimonial');
            $table->dropColumn(['patrimoniado', 'destino_patrimonial_id', 'cargo_id', 'fecha_patrimonio']);
        });
    }
}
