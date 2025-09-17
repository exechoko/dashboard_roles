<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDetalleDevolucionBodycamsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('detalle_devolucion_bodycams', function (Blueprint $table) {
             $table->id();
            $table->foreignId('devolucion_id')->constrained('devoluciones_bodycams')->onDelete('cascade')->comment('ID de la devolución');
            $table->foreignId('bodycam_id')->constrained('bodycams')->onDelete('cascade')->comment('ID de la bodycam devuelta');
            $table->enum('estado_devolucion', ['bueno', 'dañado', 'perdido'])->default('bueno')->comment('Estado en el que se devuelve la bodycam');
            $table->text('observaciones')->nullable()->comment('Observaciones específicas de la devolución de esta bodycam');
            $table->timestamps();

            // Índices para optimizar consultas
            $table->index(['devolucion_id']);
            $table->index(['bodycam_id']);
            $table->index(['estado_devolucion']);

            // Índice único compuesto para evitar duplicados
            $table->unique(['devolucion_id', 'bodycam_id'], 'unique_devolucion_bodycam');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('detalle_devolucion_bodycams');
    }
}
