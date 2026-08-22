<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAccesoriosToEquiposTable extends Migration
{
    /**
     * Run the migrations.
     *
     * Releva por equipo qué accesorios le faltan para poder salir a la calle.
     * Son nullable a propósito: NULL = todavía no se relevó (o no aplica a ese
     * modelo), 1 = lo tiene, 0 = le falta. Solo el 0 degrada la disponibilidad.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('equipos', function (Blueprint $table) {
            $table->boolean('gps')->nullable()->after('numero_segunda_bateria');
            $table->string('desc_gps')->nullable()->after('gps');
            $table->boolean('frente_remoto')->nullable()->after('desc_gps');
            $table->string('desc_frente')->nullable()->after('frente_remoto');
            $table->boolean('rf')->nullable()->after('desc_frente');
            $table->string('desc_rf')->nullable()->after('rf');
            $table->boolean('kit_inst')->nullable()->after('desc_rf');
            $table->string('desc_kit_inst')->nullable()->after('kit_inst');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('equipos', function (Blueprint $table) {
            $table->dropColumn([
                'gps',
                'desc_gps',
                'frente_remoto',
                'desc_frente',
                'rf',
                'desc_rf',
                'kit_inst',
                'desc_kit_inst',
            ]);
        });
    }
}
