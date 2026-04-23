<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manuales_documentos', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo', ['cecoco', 'instructivo']);
            $table->string('nombre_original');
            $table->string('nombre_archivo');
            $table->string('ruta_archivo');
            $table->string('extension', 10);
            $table->unsignedBigInteger('tamano')->comment('bytes');
            $table->unsignedBigInteger('subido_por')->nullable();
            $table->foreign('subido_por')->references('id')->on('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manuales_documentos');
    }
};
