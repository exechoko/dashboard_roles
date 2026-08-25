<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('llamadas_central_telefonica', function (Blueprint $table) {
            $table->string('tipo_llamada', 20)->nullable()->after('final_dnis');
            $table->index('tipo_llamada');
        });

        Schema::table('llamadas_central_telefonica', function (Blueprint $table) {
            $table->dropIndex(['es_interna']);
            $table->dropColumn('es_interna');
        });
    }

    public function down(): void
    {
        Schema::table('llamadas_central_telefonica', function (Blueprint $table) {
            $table->boolean('es_interna')->default(false)->after('final_dnis');
            $table->index('es_interna');
        });

        Schema::table('llamadas_central_telefonica', function (Blueprint $table) {
            $table->dropIndex(['tipo_llamada']);
            $table->dropColumn('tipo_llamada');
        });
    }
};
