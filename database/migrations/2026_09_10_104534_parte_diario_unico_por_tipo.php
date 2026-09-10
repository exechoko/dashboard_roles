<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El parte diario pasó a armarse consolidado a nivel División (un parte de
 * móviles + un parte de motos por turno), no uno por sección. La unicidad
 * ahora contempla el tipo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partes_diarios', function (Blueprint $table) {
            // El índice nuevo también empieza por destino_id, así que puede
            // cubrir la FK una vez que se agrega; recién ahí se borra el viejo.
            $table->unique(['destino_id', 'tipo', 'fecha_inicio']);
            $table->dropUnique('partes_diarios_destino_id_fecha_inicio_unique');
        });
    }

    public function down(): void
    {
        Schema::table('partes_diarios', function (Blueprint $table) {
            $table->dropUnique(['destino_id', 'tipo', 'fecha_inicio']);
            $table->unique(['destino_id', 'fecha_inicio']);
        });
    }
};
