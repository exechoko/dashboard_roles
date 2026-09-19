<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dominios_alerta', function (Blueprint $table) {
            $table->string('notificar_a', 255)->nullable()->after('funcionario_carga');
        });

        Schema::table('personas_alerta', function (Blueprint $table) {
            $table->string('notificar_a', 255)->nullable()->after('funcionario_carga');
        });
    }

    public function down(): void
    {
        Schema::table('dominios_alerta', function (Blueprint $table) {
            $table->dropColumn('notificar_a');
        });

        Schema::table('personas_alerta', function (Blueprint $table) {
            $table->dropColumn('notificar_a');
        });
    }
};
