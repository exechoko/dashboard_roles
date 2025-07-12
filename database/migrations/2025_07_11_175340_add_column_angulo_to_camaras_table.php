<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnAnguloToCamarasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('camaras', function (Blueprint $table) {
            $table->string('orientacion')->default('NORTE')->after('longitud')->comment('Orientación de la cámara');
            $table->integer('angulo')->default(60)->after('orientacion')->comment('Ángulo  visión de la cámara en grados');
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
            $table->dropColumn('angulo');
            $table->dropColumn('orientacion');
        });
    }
}
