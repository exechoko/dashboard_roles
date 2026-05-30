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
        Schema::create('patrimonio_cargo_movimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cargo_id')->constrained('patrimonio_cargos')->cascadeOnDelete();
            $table->foreignId('equipo_id')->constrained('equipos')->onDelete('restrict');
            $table->unsignedBigInteger('flota_id')->nullable();
            $table->unsignedBigInteger('destino_origen_id')->nullable();
            $table->unsignedBigInteger('destino_destino_id')->nullable();
            $table->unsignedBigInteger('historico_id')->nullable();
            $table->unsignedBigInteger('tipo_movimiento_id')->nullable();
            $table->string('motivo')->nullable();
            $table->string('usuario', 100)->nullable();
            $table->timestamp('fecha')->nullable();
            $table->timestamps();

            $table->index('cargo_id', 'idx_cargo_mov_cargo');
            $table->index('equipo_id', 'idx_cargo_mov_equipo');

            $table->foreign('flota_id')->references('id')->on('flota_general')->onDelete('set null');
            $table->foreign('destino_origen_id')->references('id')->on('destino')->onDelete('set null');
            $table->foreign('destino_destino_id')->references('id')->on('destino')->onDelete('set null');
            $table->foreign('historico_id')->references('id')->on('historico')->onDelete('set null');
            $table->foreign('tipo_movimiento_id')->references('id')->on('tipo_movimiento')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patrimonio_cargo_movimientos');
    }
};
