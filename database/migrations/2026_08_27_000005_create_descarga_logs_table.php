<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('descarga_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('archivo_id')->constrained('descarga_archivos')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->unsignedBigInteger('link_publico_id')->nullable();
            $table->timestamp('downloaded_at');

            $table->index(['archivo_id', 'downloaded_at']);
            $table->index(['user_id', 'downloaded_at']);
            $table->index(['link_publico_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('descarga_logs');
    }
};
