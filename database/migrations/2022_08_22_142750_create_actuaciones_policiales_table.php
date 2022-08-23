<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateActuacionesPolicialesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('actuaciones_policiales', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('equipo_id')->nullable();
            $table->unsignedBigInteger('destino_id')->nullable();
            $table->string('actuacion')->nullable();
            $table->string('funcionario')->nullable();
            $table->string('lp_funcionario')->nullable();
            $table->string('detalle')->nullable();
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
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
        Schema::dropIfExists('actuaciones_policiales');
    }
}
