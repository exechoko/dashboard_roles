<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDetalleEntregasEquiposTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('detalle_entregas_equipos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entrega_id')->constrained('entregas_equipos')->onDelete('cascade');
            $table->foreignId('equipo_id')->constrained('flota_general')->onDelete('cascade');
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
        Schema::dropIfExists('detalle_entregas_equipos');
    }
}
