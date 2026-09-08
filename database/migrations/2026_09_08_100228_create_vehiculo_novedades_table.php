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
        Schema::create('vehiculo_novedades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehiculo_id')->constrained('vehiculos')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('descripcion');
            $table->unsignedInteger('km_actuales')->nullable();
            $table->date('fecha_novedad');
            $table->boolean('resuelta')->default(false);
            $table->timestamps();

            $table->index('vehiculo_id');
            $table->index('resuelta');
            $table->index('fecha_novedad');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehiculo_novedades');
    }
};
