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
        Schema::create('dispositivos_edificio', function (Blueprint $table) {
            $table->id();

            // Tipo de dispositivo
            $table->enum('tipo', [
                'pc',
                'puesto_cecoco',
                'puesto_video',
                'router',
                'switch',
                'camara_interna'
            ])->index();

            // Información básica
            $table->string('nombre', 200);
            $table->string('ip', 45)->nullable(); // IPv4 o IPv6
            $table->string('mac', 17)->nullable(); // Dirección MAC
            $table->string('marca', 100)->nullable();
            $table->string('modelo', 100)->nullable();
            $table->string('serie', 100)->nullable();

            // Ubicación
            $table->string('oficina', 200);
            $table->string('piso', 50)->nullable();

            // Posición en el SVG
            $table->decimal('posicion_x', 10, 4)->nullable();
            $table->decimal('posicion_y', 10, 4)->nullable();

            // Información específica por tipo
            $table->string('sistema_operativo', 100)->nullable(); // Para PCs
            $table->integer('puertos')->nullable(); // Para routers/switches

            // Relación con PasswordVault para credenciales
            $table->foreignId('password_vault_id')->nullable()->constrained('password_vaults');

            // Información adicional
            $table->text('observaciones')->nullable();

            // Estado
            $table->boolean('activo')->default(true)->index();

            // Auditoría
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');

            $table->timestamps();

            // Índices
            $table->index(['tipo', 'activo']);
            $table->index(['oficina', 'piso']);
            $table->index(['ip']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dispositivos_edificio');
    }
};
