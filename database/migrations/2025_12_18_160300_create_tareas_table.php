<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTareasTable extends Migration
{
    public function up()
    {
        Schema::create('tareas', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('nombre', 200);
            $table->text('descripcion')->nullable();

            $table->string('recurrencia_tipo', 20)->default('none');
            $table->unsignedInteger('recurrencia_intervalo')->default(1);
            $table->unsignedTinyInteger('recurrencia_dia_semana')->nullable();
            $table->unsignedTinyInteger('recurrencia_dia_mes')->nullable();

            $table->date('fecha_inicio')->nullable();
            $table->boolean('activa')->default(true);

            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();

            $table->index('nombre');
            $table->index('recurrencia_tipo');
            $table->index('activa');
        });
    }

    public function down()
    {
        Schema::dropIfExists('tareas');
    }
}
