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
        Schema::create('chat_adjuntos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_mensaje_id')->constrained()->cascadeOnDelete();
            $table->string('nombre_original');
            $table->string('ruta');
            $table->string('mime', 120);
            $table->unsignedBigInteger('tamano');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_adjuntos');
    }
};
