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
            // null = sin solicitar | pendiente | procesando | completado | error
            $table->string('resumen_ia_estado', 20)->nullable()->after('resumen_ia_generado_en');
            $table->text('resumen_ia_error')->nullable()->after('resumen_ia_estado');
            $table->index('resumen_ia_estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('detalle_expediente_cecoco', function (Blueprint $table) {
            $table->dropIndex(['resumen_ia_estado']);
            $table->dropColumn(['resumen_ia_estado', 'resumen_ia_error']);
        });
    }
};
