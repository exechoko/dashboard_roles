<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal_seccion_nota_comparticiones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nota_id')->constrained('personal_seccion_notas')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('compartido_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['nota_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_seccion_nota_comparticiones');
    }
};
