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
        Schema::table('recursos', function (Blueprint $table) {
            $table->dateTime('fecha_transferencia')->nullable()->after('observaciones');
            $table->unsignedBigInteger('destino_transferencia_id')->nullable()->after('fecha_transferencia');
            $table->string('reparticion_transferencia')->nullable()->after('destino_transferencia_id');
            $table->text('observaciones_transferencia')->nullable()->after('reparticion_transferencia');

            $table->foreign('destino_transferencia_id')->references('id')->on('destino')->nullOnDelete();
            $table->index('fecha_transferencia');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recursos', function (Blueprint $table) {
            $table->dropForeign(['destino_transferencia_id']);
            $table->dropIndex(['fecha_transferencia']);
            $table->dropColumn([
                'fecha_transferencia',
                'destino_transferencia_id',
                'reparticion_transferencia',
                'observaciones_transferencia',
            ]);
        });
    }
};
