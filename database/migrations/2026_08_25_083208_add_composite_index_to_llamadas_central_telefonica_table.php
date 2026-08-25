<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('llamadas_central_telefonica', function (Blueprint $table) {
            $table->index(['calldate', 'tipo_llamada', 'atendida'], 'llamadas_central_telefonica_calldate_tipo_atendida_index');
        });
    }

    public function down(): void
    {
        Schema::table('llamadas_central_telefonica', function (Blueprint $table) {
            $table->dropIndex('llamadas_central_telefonica_calldate_tipo_atendida_index');
        });
    }
};
