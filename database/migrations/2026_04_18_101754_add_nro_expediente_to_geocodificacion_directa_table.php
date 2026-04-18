<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('geocodificacion_directa', function (Blueprint $table) {
            $table->string('nro_expediente', 100)->nullable()->after('fuente');
        });
    }

    public function down(): void
    {
        Schema::table('geocodificacion_directa', function (Blueprint $table) {
            $table->dropColumn('nro_expediente');
        });
    }
};
