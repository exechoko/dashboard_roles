<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dominios_alerta', function (Blueprint $table) {
            $table->id();
            $table->string('dominio', 15);
            $table->string('marca', 100)->nullable();
            $table->string('modelo', 100)->nullable();
            $table->string('color', 50)->nullable();
            $table->text('motivo')->nullable();
            $table->string('solicitado_por', 150)->nullable();
            $table->string('funcionario_carga', 150)->nullable();
            $table->text('camara_texto')->nullable();
            $table->date('fecha_hecho')->nullable();
            $table->date('fecha_carga')->nullable();
            $table->boolean('activo')->default(true);
            $table->text('observaciones')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('dominio');
            $table->index('activo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dominios_alerta');
    }
};
