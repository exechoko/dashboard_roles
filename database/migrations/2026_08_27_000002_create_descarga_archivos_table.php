<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('descarga_archivos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categoria_id')->constrained('descarga_categorias')->cascadeOnDelete();
            $table->string('nombre_original', 255);
            $table->string('nombre_archivo', 255);
            $table->string('ruta_relativa', 500);
            $table->string('mime_type', 100);
            $table->string('extension', 20);
            $table->unsignedBigInteger('tamano_bytes');
            $table->text('descripcion')->nullable();
            $table->boolean('destacado')->default(false);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('descargas_count')->default(0);
            $table->timestamp('expira_at')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index(['categoria_id', 'activo']);
            $table->index(['user_id']);
            $table->index(['expira_at']);
            $table->index(['destacado']);
            $table->fullText(['nombre_original', 'descripcion']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('descarga_archivos');
    }
};
