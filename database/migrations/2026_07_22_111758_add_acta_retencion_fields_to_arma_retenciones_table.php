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
        Schema::table('arma_retenciones', function (Blueprint $table) {
            $table->string('ciudad')->nullable()->after('observaciones');
            $table->time('hora_posesion')->nullable()->after('ciudad');
            $table->string('marca_modelo')->nullable()->after('hora_posesion');
            $table->string('estado_conservacion')->nullable()->after('marca_modelo');
            $table->boolean('con_cargador')->default(false)->after('estado_conservacion');
            $table->boolean('con_cartucheria')->default(false)->after('con_cargador');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('arma_retenciones', function (Blueprint $table) {
            $table->dropColumn([
                'ciudad',
                'hora_posesion',
                'marca_modelo',
                'estado_conservacion',
                'con_cargador',
                'con_cartucheria',
            ]);
        });
    }
};
