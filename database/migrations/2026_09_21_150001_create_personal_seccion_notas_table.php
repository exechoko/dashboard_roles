<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal_seccion_notas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('personal_id')->constrained('personals')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('texto');
            $table->timestamps();

            $table->index(['personal_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_seccion_notas');
    }
};
