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
            $table->json('resumen_ia')->nullable()->after('detalle_json');
            $table->dateTime('resumen_ia_generado_en')->nullable()->after('resumen_ia');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('detalle_expediente_cecoco', function (Blueprint $table) {
            $table->dropColumn(['resumen_ia', 'resumen_ia_generado_en']);
        });
    }
};
