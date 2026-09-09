<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parte_diario_consignas', function (Blueprint $table) {
            $table->id();
            $table->string('grupo', 80)->nullable();
            $table->string('nombre', 120);
            $table->unsignedSmallInteger('orden')->default(0);
            $table->boolean('activa')->default(true);
            $table->timestamps();

            $table->index('activa');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parte_diario_consignas');
    }
};
