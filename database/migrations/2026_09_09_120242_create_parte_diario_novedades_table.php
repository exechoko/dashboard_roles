<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parte_diario_novedades', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->string('guardia', 20);
            $table->string('horario', 20);
            $table->dateTime('fecha_inicio')->nullable();
            $table->dateTime('fecha_fin')->nullable();
            $table->json('contenido')->nullable();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['fecha', 'guardia']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parte_diario_novedades');
    }
};
