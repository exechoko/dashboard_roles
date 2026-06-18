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
        Schema::create('web_dependencias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('categoria');
            $table->string('direccion')->nullable();
            $table->json('telefonos')->nullable();
            $table->json('tags')->nullable();
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();

            $table->index(['categoria', 'orden']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('web_dependencias');
    }
};
