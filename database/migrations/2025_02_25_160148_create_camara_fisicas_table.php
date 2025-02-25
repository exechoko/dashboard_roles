<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCamaraFisicasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('camara_fisicas', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('tipo_camara_id')->nullable();
            $table->string('numero_serie')->nullable();
            $table->string('estado')->nullable()->default('disponible'); // "disponible", "instalada", "mantenimiento", "reparacion";
            $table->string('remito')->nullable();
            $table->date('fecha_remito')->nullable();
            $table->string('observacion')->nullable();
            $table->boolean('activo')->default(false);
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
        Schema::dropIfExists('camara_fisicas');
    }
}
