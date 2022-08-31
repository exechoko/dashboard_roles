<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDestinoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('destino', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('direccion_id')->nullable();
            $table->unsignedBigInteger('departamental_id')->nullable();
            $table->unsignedBigInteger('destacamento_id')->nullable();
            $table->unsignedBigInteger('comisaria_id')->nullable();
            $table->unsignedBigInteger('division_id')->nullable();
            $table->unsignedBigInteger('seccion_id')->nullable();
            $table->unsignedBigInteger('empresa_soporte_id')->nullable();
            $table->string('nombre')->nullable();
            $table->string('telefono', 100)->nullable();
            $table->string('ubicacion')->nullable();
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
        Schema::dropIfExists('destino');
    }
}
