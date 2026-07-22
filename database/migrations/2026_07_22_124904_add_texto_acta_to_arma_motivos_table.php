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
        Schema::table('arma_motivos', function (Blueprint $table) {
            $table->string('texto_acta', 500)->nullable()->after('nombre');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('arma_motivos', function (Blueprint $table) {
            $table->dropColumn('texto_acta');
        });
    }
};
