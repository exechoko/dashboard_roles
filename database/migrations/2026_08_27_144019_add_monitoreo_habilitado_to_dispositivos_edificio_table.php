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
        Schema::table('dispositivos_edificio', function (Blueprint $table) {
            // Independiente de "activo" (que indica si el dispositivo existe en
            // el inventario): esto pausa el ping/SNMP de infraestructura:monitorear
            // sin sacarlo del Plano 911 ni de las pantallas de Infraestructura.
            $table->boolean('monitoreo_habilitado')->default(true)->after('activo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dispositivos_edificio', function (Blueprint $table) {
            $table->dropColumn('monitoreo_habilitado');
        });
    }
};
