<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Permite registrar descargas de ZIPs (archivo_id = null)
     * además de descargas de archivos individuales.
     */
    public function up(): void
    {
        Schema::table('descarga_logs', function (Blueprint $table) {
            $table->foreignId('archivo_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Primero eliminar los registros con archivo_id null
        DB::table('descarga_logs')->whereNull('archivo_id')->delete();

        Schema::table('descarga_logs', function (Blueprint $table) {
            $table->foreignId('archivo_id')->nullable(false)->change();
        });
    }
};
