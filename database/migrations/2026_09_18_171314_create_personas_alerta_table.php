<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personas_alerta', function (Blueprint $table) {
            $table->id();
            $table->string('dni', 20)->nullable();
            $table->string('apellido_nombre', 150);
            $table->string('direccion', 255)->nullable();
            $table->text('motivo')->nullable();
            $table->string('solicitado_por', 150)->nullable();
            $table->string('funcionario_carga', 150)->nullable();
            $table->boolean('identificado')->default(false);
            $table->boolean('finalizado')->default(false);
            $table->string('foto')->nullable();
            $table->date('fecha_hecho')->nullable();
            $table->date('fecha_carga')->nullable();
            $table->boolean('activo')->default(true);
            $table->text('observaciones')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('dni');
            $table->index('activo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personas_alerta');
    }
};
