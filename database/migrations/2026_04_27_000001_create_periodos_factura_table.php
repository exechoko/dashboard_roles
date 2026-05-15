<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePeriodosFacturaTable extends Migration
{
    public function up()
    {
        Schema::create('periodos_factura', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('numero')->unique();
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->unsignedInteger('dias')->default(0);
            $table->unsignedBigInteger('minutos_totales')->default(0);

            // Cantidad total de unidades por sistema en este período
            $table->unsignedSmallInteger('n_total_tetra')->default(0);
            $table->unsignedSmallInteger('n_total_camaras')->default(0);
            $table->unsignedSmallInteger('n_total_puestos_cecoco')->default(0);

            // Datos de facturación del período
            $table->string('factura_numero', 50)->nullable();
            $table->decimal('factura_monto', 18, 2)->nullable();
            $table->string('expediente_numero', 50)->nullable();
            $table->string('ru_numero', 50)->nullable();

            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('periodos_factura');
    }
}
