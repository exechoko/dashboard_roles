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
        Schema::create('personal_licencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('personal_id')->constrained('personals')->cascadeOnDelete();
            $table->unsignedInteger('personal911_licencia_id')->unique();
            $table->unsignedInteger('personal911_funcionario_id')->index();
            $table->unsignedInteger('tipo_licencia_id')->nullable();
            $table->string('tipo_licencia')->nullable();
            $table->text('motivo')->nullable();
            $table->date('fecha_inicio')->nullable();
            $table->unsignedInteger('cantidad_dias')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->timestamps();

            $table->index(['personal_id', 'fecha_inicio', 'fecha_fin']);
            $table->index(['tipo_licencia_id', 'fecha_inicio', 'fecha_fin']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personal_licencias');
    }
};
