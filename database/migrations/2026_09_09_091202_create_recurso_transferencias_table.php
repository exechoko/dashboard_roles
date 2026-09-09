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
        Schema::create('recurso_transferencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recurso_id')->constrained('recursos')->cascadeOnDelete();
            $table->unsignedBigInteger('vehiculo_id')->nullable();
            $table->unsignedBigInteger('destino_transferencia_id')->nullable();
            $table->string('reparticion_texto')->nullable();
            $table->date('fecha_transferencia');
            $table->text('observaciones')->nullable();
            $table->string('estado', 20)->default('pendiente');
            $table->foreignId('user_id_reporte')->constrained('users')->restrictOnDelete();
            $table->unsignedBigInteger('user_id_resolucion')->nullable();
            $table->dateTime('fecha_resolucion')->nullable();
            $table->string('motivo_rechazo', 500)->nullable();
            $table->timestamps();

            $table->foreign('vehiculo_id')->references('id')->on('vehiculos')->nullOnDelete();
            $table->foreign('destino_transferencia_id')->references('id')->on('destino')->nullOnDelete();
            $table->foreign('user_id_resolucion')->references('id')->on('users')->nullOnDelete();
            $table->index('estado');
            $table->index('recurso_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recurso_transferencias');
    }
};
