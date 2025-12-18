<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTareaItemsTable extends Migration
{
    public function up()
    {
        Schema::create('tarea_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('tarea_id');

            $table->date('fecha_programada');

            $table->string('estado', 20)->default('pendiente');
            $table->unsignedBigInteger('realizado_por')->nullable();
            $table->dateTime('fecha_realizada')->nullable();
            $table->text('observaciones')->nullable();

            $table->timestamps();

            $table->index('tarea_id');
            $table->index('estado');
            $table->index('fecha_programada');
            $table->index('fecha_realizada');
            $table->index('realizado_por');
            $table->unique(['tarea_id', 'fecha_programada']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('tarea_items');
    }
}
