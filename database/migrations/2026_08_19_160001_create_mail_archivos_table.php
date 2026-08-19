<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mail_archivos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buzon_id')->constrained('mail_buzones')->cascadeOnDelete();
            $table->string('nombre_archivo', 255);
            $table->string('ruta_absoluta', 500);
            $table->unsignedBigInteger('tamano_bytes')->default(0);
            $table->dateTime('mtime_archivo')->nullable();
            $table->enum('estado', ['pendiente', 'indexando', 'indexado', 'error'])->default('pendiente');
            $table->unsignedInteger('mensajes_total')->default(0);
            $table->unsignedInteger('mensajes_nuevos')->default(0);
            $table->unsignedBigInteger('bytes_procesados')->default(0);
            $table->unsignedBigInteger('offset_reanudar')->default(0);
            $table->text('error_message')->nullable();
            $table->dateTime('indexado_at')->nullable();
            $table->timestamps();

            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_archivos');
    }
};
