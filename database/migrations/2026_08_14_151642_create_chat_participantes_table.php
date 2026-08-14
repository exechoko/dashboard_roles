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
        Schema::create('chat_participantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_conversacion_id')->constrained('chat_conversaciones')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('es_admin')->default(false);
            $table->unsignedBigInteger('ultimo_leido_id')->nullable();
            $table->timestamp('ultimo_leido_at')->nullable();
            $table->timestamps();

            $table->unique(['chat_conversacion_id', 'user_id']);
            $table->index(['user_id', 'ultimo_leido_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_participantes');
    }
};
