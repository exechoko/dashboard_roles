<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evento_cecoco', function (Blueprint $table) {
            $table->id();
            $table->string('nro_expediente', 20);
            $table->dateTime('fecha_hora');
            $table->string('box', 20)->nullable();
            $table->string('operador', 100)->nullable();
            $table->text('descripcion')->nullable();
            $table->string('direccion', 255)->nullable();
            $table->string('telefono', 30)->nullable();
            $table->dateTime('fecha_cierre')->nullable();
            $table->string('tipo_servicio', 100)->nullable();
            $table->string('periodo', 7)->nullable();
            $table->unsignedSmallInteger('anio')->nullable();
            $table->unsignedTinyInteger('mes')->nullable();
            $table->unsignedBigInteger('importacion_id')->nullable();
            $table->timestamps();

            $table->unique('nro_expediente');
            $table->index('fecha_hora');
            $table->index('operador');
            $table->index('tipo_servicio');
            $table->index('periodo');
            $table->index('anio');
            $table->index('mes');
            $table->index('telefono');
            $table->index(['anio', 'mes']);
            $table->index(['periodo', 'tipo_servicio']);
            $table->index(['fecha_hora', 'tipo_servicio']);
            $table->index(['operador', 'fecha_hora']);

            $table->foreign('importacion_id')
                ->references('id')
                ->on('importaciones')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evento_cecoco');
    }
};
