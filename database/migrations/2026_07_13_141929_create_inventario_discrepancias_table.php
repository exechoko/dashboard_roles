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
        Schema::create('inventario_discrepancias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('personal_id')->constrained('personals')->cascadeOnDelete();
            $table->unsignedInteger('personal911_id')->nullable();
            $table->string('tipo', 20);
            $table->string('estado', 20)->default('activa');
            $table->string('valor_local', 100)->nullable();
            $table->string('valor_importado', 100)->nullable();
            $table->json('detalles')->nullable();
            $table->timestamp('detectado_en');
            $table->timestamp('ultima_deteccion_en');
            $table->timestamp('resuelto_en')->nullable();
            $table->foreignId('corregido_por')->nullable()->constrained('users')->nullOnDelete();
            $table->text('motivo')->nullable();
            $table->timestamps();

            $table->unique(['personal_id', 'tipo']);
            $table->index(['estado', 'tipo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventario_discrepancias');
    }
};
