<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sinonimos_calles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('calle_id');
            $table->string('alias', 150);
            $table->unsignedBigInteger('localidad_id')->nullable();
            $table->string('origen', 30)->default('georef');
            $table->unsignedTinyInteger('confianza')->default(90);
            $table->timestamps();

            $table->unique(['alias', 'localidad_id']);
            $table->index(['alias', 'localidad_id']);
            $table->foreign('calle_id')->references('id')->on('calles')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sinonimos_calles');
    }
};
