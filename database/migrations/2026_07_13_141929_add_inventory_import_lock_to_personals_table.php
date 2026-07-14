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
        Schema::table('personals', function (Blueprint $table) {
            $table->boolean('arma_importacion_bloqueada')->default(false)->after('nro_chaleco');
            $table->boolean('chaleco_importacion_bloqueada')->default(false)->after('arma_importacion_bloqueada');
            $table->foreignId('inventario_bloqueado_por')->nullable()->after('updated_by')->constrained('users')->nullOnDelete();
            $table->timestamp('inventario_bloqueado_en')->nullable()->after('inventario_bloqueado_por');
            $table->text('inventario_bloqueo_motivo')->nullable()->after('inventario_bloqueado_en');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personals', function (Blueprint $table) {
            $table->dropForeign(['inventario_bloqueado_por']);
            $table->dropColumn([
                'arma_importacion_bloqueada',
                'chaleco_importacion_bloqueada',
                'inventario_bloqueado_por',
                'inventario_bloqueado_en',
                'inventario_bloqueo_motivo',
            ]);
        });
    }
};
