<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El módulo "Novedades" de Flota 911 se reemplaza por la bitácora de recursos
 * (recurso_bitacora*). No hay datos en producción sobre esta rama.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('recurso_novedad_adjuntos');
        Schema::dropIfExists('recurso_novedad_seguimientos');
        Schema::dropIfExists('recurso_novedades');
    }

    public function down(): void
    {
        Schema::create('recurso_novedades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recurso_id')->constrained('recursos')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('tipo')->default('operativa');
            $table->unsignedBigInteger('vehiculo_id_referencia')->nullable();
            $table->text('descripcion');
            $table->unsignedInteger('km_actuales')->nullable();
            $table->date('fecha_novedad');
            $table->boolean('resuelta')->default(false);
            $table->timestamps();

            $table->index('fecha_novedad');
            $table->index('resuelta');
        });

        Schema::create('recurso_novedad_seguimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('novedad_id')->constrained('recurso_novedades')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('descripcion');
            $table->timestamps();
        });

        Schema::create('recurso_novedad_adjuntos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('novedad_id')->constrained('recurso_novedades')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ruta');
            $table->string('nombre_original')->nullable();
            $table->string('mime_type')->nullable();
            $table->timestamps();
        });
    }
};
