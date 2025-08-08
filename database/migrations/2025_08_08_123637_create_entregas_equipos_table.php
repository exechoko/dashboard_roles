<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEntregasEquiposTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('entregas_equipos', function (Blueprint $table) {
            $table->id();
            $table->date('fecha_entrega');
            $table->time('hora_entrega');
            $table->string('dependencia');
            $table->string('personal_receptor');
            $table->string('legajo_receptor')->nullable();
            $table->text('motivo_operativo');
            $table->enum('estado', ['entregado', 'devuelto', 'perdido'])->default('entregado');
            $table->text('observaciones')->nullable();
            $table->string('usuario_creador');
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
        Schema::dropIfExists('entregas_equipos');
    }
}
