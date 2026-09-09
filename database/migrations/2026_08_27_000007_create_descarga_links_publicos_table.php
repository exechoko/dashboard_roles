<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('descarga_links_publicos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('archivo_id')->constrained('descarga_archivos')->cascadeOnDelete();
            $table->string('token', 64)->unique();
            $table->string('password')->nullable();
            $table->unsignedInteger('max_usos')->default(1);
            $table->unsignedInteger('usos_count')->default(0);
            $table->timestamp('expira_at');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('activo')->default(true);
            $table->timestamp('created_at');

            $table->index(['token']);
            $table->index(['expira_at', 'activo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('descarga_links_publicos');
    }
};
