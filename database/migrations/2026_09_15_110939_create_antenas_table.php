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
        Schema::create('antenas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('localidad')->nullable();
            $table->string('ubicacion')->nullable();
            $table->double('latitud')->nullable();
            $table->double('longitud')->nullable();
            $table->double('altura')->nullable();
            $table->boolean('activa')->default(true);
            $table->longText('observaciones')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('antenas');
    }
};
