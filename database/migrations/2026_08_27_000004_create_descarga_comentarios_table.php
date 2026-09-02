<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('descarga_comentarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('archivo_id')->constrained('descarga_archivos')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('comentario');
            $table->boolean('es_admin')->default(false);
            $table->timestamps();

            $table->index(['archivo_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('descarga_comentarios');
    }
};
