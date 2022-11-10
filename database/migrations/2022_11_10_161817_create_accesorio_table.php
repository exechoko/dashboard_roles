<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAccesorioTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('accesorio', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('tipo_accesorio_id')->nullable();
            $table->unsignedBigInteger('tipo_movimiento_id')->nullable();
            $table->unsignedBigInteger('estado_id')->nullable();
            $table->date('fecha_estado')->nullable();
            $table->string('nro_serie')->nullable();
            $table->string('propietario')->nullable();
            $table->string('provisto')->nullable();
            $table->string('condicion')->nullable();
            $table->string('con_garantia')->nullable();
            $table->string('fecha_venc_garantia')->nullable();
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
        Schema::dropIfExists('accesorio');
    }
}
