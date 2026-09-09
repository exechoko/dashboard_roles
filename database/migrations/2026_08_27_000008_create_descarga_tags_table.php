<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('descarga_tags', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 50);
            $table->string('slug', 50)->unique();
            $table->timestamps();
        });

        Schema::create('descarga_archivo_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('archivo_id')->constrained('descarga_archivos')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('descarga_tags')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['archivo_id', 'tag_id']);
            $table->index(['tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('descarga_archivo_tags');
        Schema::dropIfExists('descarga_tags');
    }
};
