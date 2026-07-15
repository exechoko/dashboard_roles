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
        Schema::table('tickets_ticketera', function (Blueprint $table) {
            $table->string('referencia_ticketera', 40)->nullable()->index()->after('codigo_ticketera');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets_ticketera', function (Blueprint $table) {
            $table->dropColumn('referencia_ticketera');
        });
    }
};
