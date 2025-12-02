<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePatrimonioMovimientosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('patrimonio_movimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bien_id')->constrained('patrimonio_bienes')->onDelete('cascade');
            $table->enum('tipo_movimiento', [
                'alta',
                'traslado',
                'baja_desuso',
                'baja_transferencia',
                'baja_rotura'
            ])->index();
            $table->foreignId('destino_desde_id')->nullable()->constrained('destino')->onDelete('set null');
            $table->string('ubicacion_desde', 150)->nullable();
            $table->foreignId('destino_hasta_id')->nullable()->constrained('destino')->onDelete('set null');
            $table->string('ubicacion_hasta', 150)->nullable();
            $table->datetime('fecha');
            $table->text('observaciones')->nullable();
            $table->string('usuario_creador', 100)->nullable();
            $table->timestamps();

            // Índice para consultas históricas
            $table->index(['bien_id', 'fecha'], 'idx_bien_fecha');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('patrimonio_movimientos');
    }
}
