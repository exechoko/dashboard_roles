<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddToTipoMovimientoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tipo_movimiento', function (Blueprint $table) {
            $table->boolean('habilita_campos')->after('observaciones')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tipo_movimiento', function (Blueprint $table) {
            $table->dropColumn('habilita_campos');
        });
    }
}
