<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRutasImagenesToDevolucionesEquiposTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('devoluciones_equipos', function (Blueprint $table) {
            $table->json('rutas_imagenes')->after('observaciones')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('devoluciones_equipos', function (Blueprint $table) {
            $table->dropColumn('rutas_imagenes');
        });
    }
}
