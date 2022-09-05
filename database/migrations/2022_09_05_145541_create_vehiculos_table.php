<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVehiculosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vehiculos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('tipo_vehiculo'); //Moto, Auto, Camioneta
            $table->string('marca')->nullable();
            $table->string('modelo')->nullable();
            $table->string('nro_chasis')->nullable();
            $table->string('dominio')->nullable();
            $table->string('color')->nullable();
            $table->string('propiedad')->nullable();
            $table->string('detalles')->nullable();
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
        Schema::dropIfExists('vehiculos');
    }
}
