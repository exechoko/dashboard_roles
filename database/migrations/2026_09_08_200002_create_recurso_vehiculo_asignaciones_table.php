<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurso_vehiculo_asignaciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('recurso_id');
            $table->unsignedBigInteger('vehiculo_id')->nullable();
            $table->date('fecha_desde');
            $table->date('fecha_hasta')->nullable();
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->text('motivo')->nullable();
            $table->timestamps();

            $table->foreign('recurso_id')->references('id')->on('recursos')->onDelete('cascade');
            $table->foreign('vehiculo_id')->references('id')->on('vehiculos')->onDelete('set null');
            $table->foreign('usuario_id')->references('id')->on('users')->onDelete('set null');

            $table->index(['recurso_id', 'fecha_desde']);
        });

        // Backfill: crear asignación inicial para cada recurso que ya tiene vehiculo_id
        DB::statement("
            INSERT INTO recurso_vehiculo_asignaciones (recurso_id, vehiculo_id, fecha_desde, fecha_hasta, created_at, updated_at)
            SELECT id, vehiculo_id, DATE(IFNULL(created_at, NOW())), NULL, NOW(), NOW()
            FROM recursos
            WHERE vehiculo_id IS NOT NULL
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('recurso_vehiculo_asignaciones');
    }
};
