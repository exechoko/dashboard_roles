<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('importaciones_llamadas_central_telefonica', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_archivo', 255);
            $table->unsignedInteger('total_registros')->default(0);
            $table->unsignedInteger('registros_importados')->default(0);
            $table->unsignedInteger('registros_omitidos')->default(0);
            $table->string('estado', 20)->default('completado');
            $table->text('error_mensaje')->nullable();
            $table->unsignedInteger('tiempo_procesamiento')->nullable();
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('importaciones_llamadas_central_telefonica');
    }
};
