<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDetalleEntregaBodycamsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('detalle_entrega_bodycams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entrega_id')->constrained('entregas_bodycams')->onDelete('cascade')->comment('ID de la entrega');
            $table->foreignId('bodycam_id')->constrained('bodycams')->onDelete('cascade')->comment('ID de la bodycam');
            $table->text('observaciones')->nullable()->comment('Observaciones específicas de esta bodycam');
            $table->timestamps();

            // Índices para optimizar consultas
            $table->index(['entrega_id']);
            $table->index(['bodycam_id']);

            // Índice único compuesto para evitar duplicados
            $table->unique(['entrega_id', 'bodycam_id'], 'unique_entrega_bodycam');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('detalle_entrega_bodycams');
    }
}
