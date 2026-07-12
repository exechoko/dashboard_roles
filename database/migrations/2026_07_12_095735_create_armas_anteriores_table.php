<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('armas_anteriores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('personal_id')->constrained('personals')->cascadeOnDelete();
            $table->string('numeracion_arma', 50);
            $table->foreignId('arma_tipo_id')->constrained('arma_tipos');
            $table->string('nro_chaleco', 50)->nullable();
            $table->date('fecha_cambio');
            $table->text('motivo_cambio')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('armas_anteriores');
    }
};
