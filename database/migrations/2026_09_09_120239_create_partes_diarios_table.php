<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partes_diarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('destino_id')->constrained('destino')->restrictOnDelete();
            $table->string('tipo', 20); // moviles | motos
            $table->date('fecha');
            $table->string('guardia', 20);
            $table->string('horario', 20);
            $table->dateTime('fecha_inicio');
            $table->dateTime('fecha_fin');
            $table->text('guardia_interna')->nullable();
            $table->text('licencia_ordinaria')->nullable();
            $table->text('novedades_pie')->nullable();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['destino_id', 'fecha_inicio']);
            $table->index(['fecha', 'guardia']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partes_diarios');
    }
};
