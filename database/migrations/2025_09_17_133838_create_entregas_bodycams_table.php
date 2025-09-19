<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEntregasBodycamsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('entregas_bodycams', function (Blueprint $table) {
            $table->id();
            $table->date('fecha_entrega')->comment('Fecha de entrega');
            $table->time('hora_entrega')->comment('Hora de entrega');
            $table->string('dependencia')->comment('Dependencia receptora');
            $table->string('personal_receptor')->nullable()->comment('Nombre del personal receptor');
            $table->string('legajo_receptor', 50)->nullable()->comment('Legajo del personal receptor');
            $table->string('personal_entrega')->nullable()->comment('Nombre del personal que entrega');
            $table->string('legajo_entrega', 50)->nullable()->comment('Legajo del personal que entrega');
            $table->text('motivo_operativo')->comment('Motivo o descripción del operativo');
            $table->text('observaciones')->nullable()->comment('Observaciones adicionales');
            $table->json('rutas_imagenes')->nullable()->comment('Rutas de imágenes adjuntas en formato JSON');
            $table->text('ruta_archivo')->nullable()->comment('Ruta del documento generado');
            $table->enum('estado', ['entregada', 'parcialmente_devuelta', 'devuelta', 'perdida'])->default('entregada')->comment('Estado de la entrega');
            $table->string('usuario_creador')->comment('Usuario que creó la entrega');
            $table->timestamps();

            // Índices para optimizar búsquedas
            $table->index(['fecha_entrega']);
            $table->index(['dependencia']);
            $table->index(['estado']);
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
        Schema::dropIfExists('entregas_bodycams');
    }
}
