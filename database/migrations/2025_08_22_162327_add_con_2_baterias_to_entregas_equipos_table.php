<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCon2BateriasToEntregasEquiposTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('entregas_equipos', function (Blueprint $table) {
            $table->boolean('con_2_baterias')->after('estado')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('entregas_equipos', function (Blueprint $table) {
            $table->dropColumn('con_2_baterias');
        });
    }
}
