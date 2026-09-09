<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parte_diario_asignaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parte_diario_id')->constrained('partes_diarios')->cascadeOnDelete();
            $table->string('grupo', 80)->nullable();
            $table->string('nombre', 120);
            $table->string('asignacion_texto', 255)->nullable();
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();

            $table->index('parte_diario_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parte_diario_asignaciones');
    }
};
