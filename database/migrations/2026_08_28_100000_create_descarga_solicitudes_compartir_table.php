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
        Schema::create('descarga_solicitudes_compartir', function (Blueprint $table) {
            $table->id();
            $table->foreignId('archivo_id')->constrained('descarga_archivos')->cascadeOnDelete();
            $table->foreignId('usuario_solicita_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('usuario_destino_id')->constrained('users')->cascadeOnDelete();
            $table->text('motivo')->nullable();
            $table->enum('estado', ['pendiente', 'aprobado', 'rechazado'])->default('pendiente');
            $table->foreignId('aprobado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->text('motivo_respuesta')->nullable();
            $table->timestamp('respondido_at')->nullable();
            $table->timestamps();

            $table->index(['estado', 'created_at']);
            $table->index(['usuario_solicita_id', 'estado']);
            $table->index(['usuario_destino_id', 'estado']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('descarga_solicitudes_compartir');
    }
};
