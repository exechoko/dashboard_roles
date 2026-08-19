<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mail_mensajes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buzon_id')->constrained('mail_buzones')->cascadeOnDelete();
            $table->foreignId('archivo_id')->constrained('mail_archivos')->cascadeOnDelete();

            $table->unsignedBigInteger('byte_offset');
            $table->unsignedBigInteger('byte_length');

            $table->string('message_id', 255)->nullable();
            $table->string('gm_thread_id', 64)->nullable();

            $table->string('de_nombre', 255)->nullable();
            $table->string('de_email', 255)->nullable();
            $table->text('para')->nullable();
            $table->text('cc')->nullable();
            $table->string('responder_a', 255)->nullable();

            $table->string('asunto', 998)->nullable();
            $table->dateTime('fecha')->nullable();

            $table->string('etiquetas', 500)->nullable();
            $table->string('carpeta', 30)->default('recibidos');

            $table->boolean('tiene_adjuntos')->default(false);
            $table->unsignedSmallInteger('cantidad_adjuntos')->default(0);
            $table->json('adjuntos_json')->nullable();
            $table->text('adjuntos_nombres')->nullable();

            $table->unsignedBigInteger('tamano_bytes')->default(0);
            $table->boolean('tiene_html')->default(false);
            $table->boolean('cuerpo_truncado')->default(false);
            $table->string('snippet', 500)->nullable();
            $table->longText('cuerpo_texto')->nullable();

            $table->timestamps();

            $table->unique(['buzon_id', 'message_id'], 'mail_mensajes_buzon_message_unique');
            $table->index('de_email');
            $table->index('fecha');
            $table->index('carpeta');
            $table->fullText(['asunto', 'cuerpo_texto', 'adjuntos_nombres'], 'mail_mensajes_fulltext');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_mensajes');
    }
};
