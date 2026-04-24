<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePatrimonioCargosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('patrimonio_cargos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipo_id')->constrained('equipos')->onDelete('restrict');
            $table->foreignId('destino_id')->constrained('destino')->onDelete('restrict');
            $table->unsignedBigInteger('historico_id')->nullable();
            $table->string('firmante_nombre', 150)->nullable();
            $table->string('firmante_cargo', 150)->nullable();
            $table->string('firmante_legajo', 50)->nullable();
            $table->enum('estado', ['pendiente', 'firmado', 'rechazado'])->default('pendiente');
            $table->datetime('fecha_firma')->nullable();
            $table->text('observaciones')->nullable();
            $table->string('ruta_documento')->nullable();
            $table->string('usuario_creador', 100)->nullable();
            $table->timestamps();

            // Índices para consultas frecuentes
            $table->index(['destino_id', 'estado'], 'idx_cargo_destino_estado');
            $table->index('equipo_id', 'idx_cargo_equipo');

            // FK manual para historico (no tiene convención de nombre)
            $table->foreign('historico_id')
                ->references('id')
                ->on('historico')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('patrimonio_cargos');
    }
}
