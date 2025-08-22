<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDetalleEntregaAccesorios extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('detalle_entrega_accesorios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entrega_id')->constrained('entregas_equipos')->onDelete('cascade');
            $table->enum('tipo_accesorio', ['cuna_cargadora', 'transformador']);
            $table->integer('cantidad')->default(1);
            $table->string('marca')->nullable(); // Solo para cunas: Sepura o Teltronic
            $table->string('modelo')->nullable();
            $table->string('numero_serie')->nullable(); // Solo para cunas
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index(['entrega_id', 'tipo_accesorio']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('detalle_entrega_accesorios');
    }
}
