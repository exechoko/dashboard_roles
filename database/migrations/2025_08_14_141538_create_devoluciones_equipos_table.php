<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDevolucionesEquiposTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('devoluciones_equipos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entrega_id')->constrained('entregas_equipos')->onDelete('cascade');
            $table->date('fecha_devolucion');
            $table->time('hora_devolucion');
            $table->string('personal_devuelve')->nullable();
            $table->string('legajo_devuelve')->nullable();
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
        Schema::dropIfExists('devoluciones_equipos');
    }
}
