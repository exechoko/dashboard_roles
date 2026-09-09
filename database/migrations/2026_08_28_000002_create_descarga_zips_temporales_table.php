<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('descarga_zips_temporales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('token', 64)->unique();
            $table->string('ruta_zip', 500);
            $table->unsignedBigInteger('tamano_bytes');
            $table->timestamp('expira_at');
            $table->boolean('descargado')->default(false);
            $table->timestamp('created_at');

            $table->index(['token', 'expira_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('descarga_zips_temporales');
    }
};
