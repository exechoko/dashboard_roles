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
        Schema::create('arma_retencion_historial', function (Blueprint $table) {
            $table->id();
            $table->foreignId('arma_retencion_id')->constrained('arma_retenciones')->cascadeOnDelete();
            $table->string('accion', 30);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('comentario')->nullable();
            $table->json('datos_adicionales')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['arma_retencion_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('arma_retencion_historial');
    }
};
