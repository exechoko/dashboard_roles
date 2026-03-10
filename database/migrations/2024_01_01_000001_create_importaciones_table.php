<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('importaciones', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_archivo', 255);
            $table->string('periodo', 7)->nullable();
            $table->unsignedSmallInteger('anio')->nullable();
            $table->unsignedTinyInteger('mes')->nullable();
            $table->unsignedInteger('total_registros')->default(0);
            $table->unsignedInteger('registros_importados')->default(0);
            $table->unsignedInteger('registros_duplicados')->default(0);
            $table->unsignedInteger('registros_omitidos')->default(0);
            $table->unsignedInteger('registros_con_error')->default(0);
            $table->enum('estado', ['procesando', 'completado', 'error'])->default('procesando');
            $table->text('errores')->nullable();
            $table->unsignedInteger('tiempo_procesamiento')->nullable();
            $table->timestamps();

            $table->index('estado');
            $table->index('periodo');
            $table->index('anio');
            $table->index('mes');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('importaciones');
    }
};
