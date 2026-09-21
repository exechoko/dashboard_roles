<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal_secciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('personal_id')->unique()->constrained('personals')->cascadeOnDelete();
            $table->unsignedSmallInteger('id_lugar_personal911')->nullable();
            $table->string('seccion', 100)->nullable();
            $table->boolean('activo')->default(true);
            $table->boolean('en_licencia')->default(false);
            $table->string('funcion_actual', 150)->nullable();
            $table->date('fecha_alta')->nullable();
            $table->date('fecha_baja')->nullable();
            $table->string('motivo_baja', 30)->nullable();
            $table->timestamps();

            $table->index('seccion');
            $table->index('activo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_secciones');
    }
};
