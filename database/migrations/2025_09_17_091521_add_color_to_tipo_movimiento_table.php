<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColorToTipoMovimientoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tipo_movimiento', function (Blueprint $table) {
            $table->string('color', 7)->after('observaciones')->default('#28a745'); // Verde por defecto (success)
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
            $table->dropColumn('color');
        });
    }
}
