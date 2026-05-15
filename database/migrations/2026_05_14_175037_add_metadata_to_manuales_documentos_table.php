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
        Schema::table('manuales_documentos', function (Blueprint $table) {
            $table->string('titulo')->nullable()->after('tipo');
            $table->string('tematica')->nullable()->after('titulo');
            $table->index(['tipo', 'tematica']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('manuales_documentos', function (Blueprint $table) {
            $table->dropIndex(['tipo', 'tematica']);
            $table->dropColumn(['titulo', 'tematica']);
        });
    }
};
