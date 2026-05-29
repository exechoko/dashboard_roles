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
        Schema::create('entregas_combustible', function (Blueprint $table) {
            $table->id();
            $table->date('fecha_entrega');
            $table->time('hora_entrega');
            $table->string('ticket');
            $table->string('empresa_soporte')->default('Patagonia Green');
            $table->string('personal_receptor');
            $table->string('legajo_receptor')->nullable();
            $table->string('personal_entrega');
            $table->string('legajo_entrega')->nullable();
            $table->unsignedSmallInteger('cantidad_litros')->default(40);
            $table->unsignedSmallInteger('cantidad_bidones')->default(2);
            $table->unsignedSmallInteger('litros_por_bidon')->default(20);
            $table->string('combustible')->default('Puma Diesel 500');
            $table->text('observaciones')->nullable();
            $table->string('ruta_archivo')->nullable();
            $table->string('ruta_acta_firmada')->nullable();
            $table->string('usuario_creador');
            $table->timestamps();

            $table->index('fecha_entrega');
            $table->index('ticket');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entregas_combustible');
    }
};
