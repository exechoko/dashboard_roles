<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDevolucionesBodycamsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('devoluciones_bodycams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entrega_id')->constrained('entregas_bodycams')->onDelete('cascade')->comment('ID de la entrega original');
            $table->date('fecha_devolucion')->comment('Fecha de devolución');
            $table->time('hora_devolucion')->comment('Hora de devolución');
            $table->string('personal_devuelve')->nullable()->comment('Nombre del personal que devuelve');
            $table->string('legajo_devuelve', 50)->nullable()->comment('Legajo del personal que devuelve');
            $table->text('observaciones')->nullable()->comment('Observaciones de la devolución');
            $table->json('rutas_imagenes')->nullable()->comment('Rutas de imágenes de la devolución en formato JSON');
            $table->string('usuario_creador')->comment('Usuario que registró la devolución');
            $table->timestamps();

            // Índices para optimizar consultas
            $table->index(['entrega_id']);
            $table->index(['fecha_devolucion']);
            $table->index(['usuario_creador']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('devoluciones_bodycams');
    }
}
