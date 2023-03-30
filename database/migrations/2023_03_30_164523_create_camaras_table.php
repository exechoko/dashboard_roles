<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCamarasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('camaras', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('nombre')->nullable();
            $table->string('ip')->nullable();
            $table->string('tipo')->nullable();
            $table->string('inteligencia')->nullable();
            $table->string('sitio')->nullable();
            $table->float('latitud', 8, 6)->nullable();
            $table->float('longitud', 8, 6)->nullable();
            $table->date('fecha_instalacion')->nullable();
            $table->date('fecha_desintalacion')->nullable();
            $table->string('observaciones')->nullable();
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
        Schema::dropIfExists('camaras');
    }
}
