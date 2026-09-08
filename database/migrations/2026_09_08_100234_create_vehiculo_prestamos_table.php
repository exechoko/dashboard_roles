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
        Schema::create('vehiculo_prestamos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehiculo_id')->constrained('vehiculos')->cascadeOnDelete();
            $table->foreignId('destino_origen_id')->constrained('destino')->restrictOnDelete();
            $table->foreignId('destino_destino_id')->constrained('destino')->restrictOnDelete();
            $table->datetime('fecha_salida');
            $table->datetime('fecha_retorno')->nullable();
            $table->text('observaciones_salida')->nullable();
            $table->text('observaciones_retorno')->nullable();
            $table->foreignId('user_id_prestamo')->constrained('users')->restrictOnDelete();
            $table->foreignId('user_id_retorno')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index('vehiculo_id');
            $table->index('activo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehiculo_prestamos');
    }
};
