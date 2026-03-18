<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('geocodificacion_directa', function (Blueprint $table) {
            $table->id();
            $table->string('direccion_original', 500)->unique();
            $table->string('direccion_normalizada', 500)->nullable();
            $table->double('latitud')->nullable();
            $table->double('longitud')->nullable();
            $table->string('fuente', 30)->default('google');
            $table->timestamps();

            $table->index('latitud');
            $table->index('longitud');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('geocodificacion_directa');
    }
};
