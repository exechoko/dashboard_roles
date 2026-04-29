<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIncidencias911Table extends Migration
{
    public function up()
    {
        Schema::create('incidencias_911', function (Blueprint $table) {
            $table->id();
            $table->foreignId('periodo_id')->constrained('periodos_factura')->onDelete('cascade');

            // Origen del dato
            $table->enum('tipo_incidencia', ['persistente', 'transitoria', 'manual'])->default('transitoria');
            $table->string('hoja_origen', 50)->default('manual'); // preventivos, patagonia, telecom, ute, manual
            $table->string('incidencia_code', 60)->nullable();
            $table->text('tickets')->nullable(); // lista de tickets (comma-separated o texto)

            // Tiempo de falla
            $table->dateTime('fecha_inicio_falla')->nullable();
            $table->decimal('minutos_fallo', 14, 2)->default(0); // minutos dentro del período

            // Equipamiento afectado
            $table->unsignedSmallInteger('n_unidades_afectadas')->default(1);
            $table->unsignedSmallInteger('n_total_unidades')->default(1); // total de unidades de ese tipo en el período

            // Ponderación del pliego (Anexo V)
            $table->string('sistema', 100)->default(''); // N1: CCTV / TETRA / Emergencias 911 / Infraestructura / Prestación de Servicio
            $table->string('modulo_n2', 100)->default(''); // N2: Comunicación, Cámaras, etc.
            $table->decimal('ponderacion_n2', 6, 2)->default(0); // % peso N2 (ej: 100 para Comunicación TETRA)
            $table->decimal('ponderacion_n1', 6, 2)->default(0); // % peso N1 (ej: 20 para TETRA)

            // Clasificación
            $table->enum('prioridad', ['critico', 'alto', 'medio', 'bajo'])->default('medio');
            $table->boolean('aplica_calculo')->default(true);
            $table->string('estado', 50)->default('resuelto');
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('incidencias_911');
    }
}
