<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dominios_alerta', function (Blueprint $table) {
            $table->boolean('parcial')->default(false)->after('dominio');
        });
    }

    public function down(): void
    {
        Schema::table('dominios_alerta', function (Blueprint $table) {
            $table->dropColumn('parcial');
        });
    }
};
