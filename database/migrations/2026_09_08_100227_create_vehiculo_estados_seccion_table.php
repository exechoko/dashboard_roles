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
        Schema::create('vehiculo_estados_seccion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehiculo_id')->unique()->constrained('vehiculos')->cascadeOnDelete();
            $table->enum('estado', ['en_servicio', 'fuera_de_servicio', 'en_taller', 'baja_provisional'])->default('en_servicio');
            $table->text('observaciones')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehiculo_estados_seccion');
    }
};
