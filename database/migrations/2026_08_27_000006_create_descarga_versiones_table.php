<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('descarga_versiones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('archivo_id')->constrained('descarga_archivos')->cascadeOnDelete();
            $table->unsignedInteger('version_numero');
            $table->string('nombre_archivo_anterior', 255);
            $table->string('ruta_anterior', 500);
            $table->unsignedBigInteger('tamano_anterior');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('motivo')->nullable();
            $table->timestamp('created_at');

            $table->index(['archivo_id', 'version_numero']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('descarga_versiones');
    }
};
