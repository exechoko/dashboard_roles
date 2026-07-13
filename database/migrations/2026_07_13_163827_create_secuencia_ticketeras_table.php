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
        Schema::create('secuencias_ticketera', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('anio')->unique();
            $table->unsignedInteger('ultimo_numero')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('secuencias_ticketera');
    }
};
