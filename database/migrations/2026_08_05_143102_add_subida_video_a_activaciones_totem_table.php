<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activaciones_totem', function (Blueprint $table) {
            $table->string('nombre_archivo_original')->nullable()->after('observaciones');
            $table->string('ruta_archivo')->nullable()->after('nombre_archivo_original');
            $table->string('hash_sha256', 64)->nullable()->after('ruta_archivo');
            $table->string('subida_estado', 20)->nullable()->after('hash_sha256');
            $table->text('subida_error')->nullable()->after('subida_estado');
        });
    }

    public function down(): void
    {
        Schema::table('activaciones_totem', function (Blueprint $table) {
            $table->dropColumn(['nombre_archivo_original', 'ruta_archivo', 'hash_sha256', 'subida_estado', 'subida_error']);
        });
    }
};
