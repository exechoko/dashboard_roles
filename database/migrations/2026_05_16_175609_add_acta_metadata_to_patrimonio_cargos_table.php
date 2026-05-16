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
        Schema::table('patrimonio_cargos', function (Blueprint $table) {
            $table->string('acta_nombre_original')->nullable()->after('ruta_documento');
            $table->string('acta_mime', 100)->nullable()->after('acta_nombre_original');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patrimonio_cargos', function (Blueprint $table) {
            $table->dropColumn(['acta_nombre_original', 'acta_mime']);
        });
    }
};
