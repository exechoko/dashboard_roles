<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('armeria_movimientos', function (Blueprint $table) {
            $table->id();
            $table->morphs('movable');
            $table->string('accion', 30);
            $table->string('ubicacion_anterior', 30)->nullable();
            $table->string('ubicacion_nueva', 30)->nullable();
            $table->string('estado_anterior', 30)->nullable();
            $table->string('estado_nuevo', 30)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('comentario')->nullable();
            $table->json('datos_adicionales')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['movable_type', 'movable_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('armeria_movimientos');
    }
};
