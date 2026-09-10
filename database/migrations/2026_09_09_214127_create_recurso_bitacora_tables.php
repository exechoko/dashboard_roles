<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurso_bitacora', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recurso_id')->constrained('recursos')->cascadeOnDelete();
            $table->dateTime('fecha_hora');
            $table->string('categoria', 40);
            $table->text('descripcion');
            $table->string('estado', 10)->nullable(); // abierto | cerrado | null (línea sin estado)
            $table->unsignedInteger('km')->nullable();
            $table->string('taller')->nullable();
            $table->decimal('costo', 12, 2)->nullable();
            $table->dateTime('cerrada_en')->nullable();
            $table->unsignedBigInteger('cerrada_por')->nullable();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->foreign('cerrada_por')->references('id')->on('users')->nullOnDelete();
            $table->index(['recurso_id', 'fecha_hora']);
            $table->index('estado');
        });

        Schema::create('recurso_bitacora_seguimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bitacora_id')->constrained('recurso_bitacora')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->text('descripcion');
            $table->timestamps();
        });

        Schema::create('recurso_bitacora_adjuntos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bitacora_id')->nullable();
            $table->unsignedBigInteger('seguimiento_id')->nullable();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('ruta');
            $table->string('nombre_original')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('tamano')->nullable();
            $table->timestamps();

            $table->foreign('bitacora_id')->references('id')->on('recurso_bitacora')->cascadeOnDelete();
            $table->foreign('seguimiento_id')->references('id')->on('recurso_bitacora_seguimientos')->cascadeOnDelete();
            $table->index('bitacora_id');
            $table->index('seguimiento_id');
        });

        Schema::create('recurso_bitacora_vistas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recurso_id')->constrained('recursos')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->dateTime('visto_en');
            $table->timestamps();

            $table->unique(['recurso_id', 'user_id']);
        });

        Schema::create('recurso_bitacora_solicitudes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bitacora_id')->constrained('recurso_bitacora')->cascadeOnDelete();
            $table->string('tipo', 12); // edicion | eliminacion
            $table->json('cambios')->nullable();
            $table->string('estado', 12)->default('pendiente'); // pendiente | aprobada | rechazada
            $table->string('motivo', 500)->nullable();
            $table->string('motivo_resolucion', 500)->nullable();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->unsignedBigInteger('resuelta_por')->nullable();
            $table->dateTime('resuelta_en')->nullable();
            $table->timestamps();

            $table->foreign('resuelta_por')->references('id')->on('users')->nullOnDelete();
            $table->index(['bitacora_id', 'estado']);
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurso_bitacora_solicitudes');
        Schema::dropIfExists('recurso_bitacora_vistas');
        Schema::dropIfExists('recurso_bitacora_adjuntos');
        Schema::dropIfExists('recurso_bitacora_seguimientos');
        Schema::dropIfExists('recurso_bitacora');
    }
};
