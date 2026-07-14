<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inventario_conflictos', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 20);
            $table->string('identificador', 50);
            $table->string('estado', 20)->default('activo');
            $table->json('detalles');
            $table->timestamp('detectado_en');
            $table->timestamp('ultima_deteccion_en');
            $table->timestamp('resuelto_en')->nullable();
            $table->timestamps();

            $table->unique(['tipo', 'identificador']);
            $table->index(['estado', 'tipo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventario_conflictos');
    }
};
