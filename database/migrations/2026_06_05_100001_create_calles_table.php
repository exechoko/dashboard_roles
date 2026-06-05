<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('calles', function (Blueprint $table) {
            $table->id();
            $table->string('georef_id', 20)->unique()->nullable();
            $table->string('calle', 250);
            $table->string('tipo', 50)->nullable();
            $table->string('calle_normalizada', 250)->nullable();
            $table->integer('altura_inicio')->default(0);
            $table->integer('altura_fin')->default(0);
            $table->string('localidad', 150)->nullable();
            $table->unsignedBigInteger('localidad_id')->nullable();
            $table->string('provincia', 100)->nullable();
            $table->string('cp', 20)->nullable();
            $table->string('user', 50)->nullable();
            $table->timestamps();

            $table->index('calle_normalizada');
            $table->index('localidad_id');
            $table->index(['localidad_id', 'altura_inicio', 'altura_fin']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calles');
    }
};
