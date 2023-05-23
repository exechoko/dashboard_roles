<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTipoCamaraTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tipo_camara', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('tipo')->nullable(); /*Fija, Fija FR, Fija LPR, Domo, Domo Dual*/
            $table->string('marca')->nullable();
            $table->string('modelo')->nullable();
            $table->string('imagen')->nullable();
            $table->longText('observaciones')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tipo_camara');
    }
}
