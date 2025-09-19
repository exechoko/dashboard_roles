<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFlotaBodycamsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bodycams', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique()->comment('Código único de identificación');
            $table->string('imei')->unique()->comment('Número IMEI de la bodycam');
            $table->string('numero_serie')->unique()->comment('Número de serie de la bodycam');
            $table->string('marca')->comment('Marca de la bodycam');
            $table->string('modelo')->comment('Modelo de la bodycam');
            $table->string('numero_tarjeta_sd')->nullable()->comment('Número de serie de la tarjeta SD');
            $table->string('numero_bateria')->nullable()->comment('Número de serie de la batería');
            $table->enum('estado', ['disponible', 'entregada', 'perdida', 'mantenimiento', 'dada_baja'])->default('disponible')->comment('Estado actual de la bodycam');
            $table->date('fecha_adquisicion')->nullable()->comment('Fecha de adquisición');
            $table->text('observaciones')->nullable()->comment('Observaciones generales');
            $table->string('usuario_creador')->comment('Usuario que registró la bodycam');
            $table->timestamps();

            // Índices para optimizar búsquedas
            $table->index(['estado']);
            $table->index(['codigo']);
            $table->index(['numero_serie']);
            $table->index(['marca', 'modelo']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bodycams');
    }
}
