<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('armeria_adjuntos', function (Blueprint $table) {
            $table->id();
            $table->morphs('adjuntable');
            $table->string('tipo', 20);
            $table->string('ruta', 255);
            $table->string('nombre_original', 255);
            $table->string('mime', 100)->nullable();
            $table->unsignedBigInteger('tamano')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('armeria_adjuntos');
    }
};
