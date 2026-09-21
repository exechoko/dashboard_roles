<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * El ENUM de `recurso_estado_diario.estado_dia` quedó desactualizado: solo
 * permitía ('circula','reserva','fuera_de_servicio','otro'), pero el modelo
 * RecursoEstadoDiario ya ofrecía 'qap_playon', 'a_presto', 'de_traslado' y
 * 'en_comision' desde el rework del parte diario, lo que producía un
 * "Data truncated for column 'estado_dia'" al guardar esos estados.
 * Se pasa a VARCHAR (mismo criterio ya usado para la columna `horario`)
 * para que agregar estados no vuelva a requerir tocar el esquema.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("UPDATE recurso_estado_diario SET estado_dia = 'qap_playon' WHERE estado_dia = 'reserva'");
        DB::statement("ALTER TABLE recurso_estado_diario MODIFY estado_dia VARCHAR(30) NOT NULL DEFAULT 'circula'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE recurso_estado_diario MODIFY estado_dia ENUM('circula','reserva','fuera_de_servicio','otro') NOT NULL DEFAULT 'circula'");
    }
};
