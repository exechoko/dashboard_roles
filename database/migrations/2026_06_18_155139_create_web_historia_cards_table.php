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
        Schema::create('web_historia_cards', function (Blueprint $table) {
            $table->id();
            $table->string('anio', 40);
            $table->string('titulo');
            $table->text('texto');
            $table->string('tag')->nullable();
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();

            $table->index('orden');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('web_historia_cards');
    }
};
