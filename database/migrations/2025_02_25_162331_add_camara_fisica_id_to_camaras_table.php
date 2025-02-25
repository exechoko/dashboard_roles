<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCamaraFisicaIdToCamarasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('camaras', function (Blueprint $table) {
            $table->unsignedBigInteger('camara_fisica_id')->nullable()->after('tipo_camara_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('camaras', function (Blueprint $table) {
            $table->dropColumn('camara_fisica_id');
        });
    }
}
