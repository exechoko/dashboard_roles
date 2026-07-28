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
        Schema::create('chatbot_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chatbot_conversation_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20);
            $table->text('content')->nullable();
            $table->string('status', 20)->default('completed');
            $table->string('context_path', 255)->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['chatbot_conversation_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chatbot_messages');
    }
};
