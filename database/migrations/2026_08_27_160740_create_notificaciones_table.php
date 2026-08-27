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
        Schema::create('notificaciones', function (Blueprint $table) {
            $table->id();
            $table->string('categoria')->default('infraestructura');
            $table->string('tipo');
            $table->string('nivel')->default('warning');
            $table->string('titulo');
            $table->text('mensaje');
            $table->foreignId('dispositivo_edificio_id')->nullable()
                ->constrained('dispositivos_edificio')->nullOnDelete();
            $table->json('datos')->nullable();
            $table->timestamps();

            $table->index(['categoria', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notificaciones');
    }
};
