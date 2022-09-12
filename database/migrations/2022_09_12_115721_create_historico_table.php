<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHistoricoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('historico', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('equipo_id')->nullable();
            $table->unsignedBigInteger('recurso_id')->nullable();
            $table->unsignedBigInteger('destino_id')->nullable();
            $table->unsignedBigInteger('actuacion_policial_id')->nullable();
            $table->unsignedBigInteger('tipo_movimiento_id')->nullable();
            $table->date('fecha_asignacion')->nullable();
            $table->date('fecha_desasignacion')->nullable();
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
        Schema::dropIfExists('historico');
    }
}
