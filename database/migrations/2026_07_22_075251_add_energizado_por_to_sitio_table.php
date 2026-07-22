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
        Schema::table('sitio', function (Blueprint $table) {
            $table->string('energizado_por')->nullable()->after('activo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sitio', function (Blueprint $table) {
            $table->dropColumn('energizado_por');
        });
    }
};
