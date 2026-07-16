<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('constancias_credenciales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('nombre_apellido');
            $table->string('dni');
            $table->string('email');
            $table->string('contrasena');
            $table->string('lugar')->default('Paraná, Entre Ríos');
            $table->date('fecha_entrega');
            $table->boolean('firmada')->default(false);
            $table->dateTime('fecha_firma')->nullable();
            $table->string('ruta_archivo')->nullable();
            $table->string('ruta_archivo_firmado')->nullable();
            $table->boolean('email_enviado')->default(false);
            $table->dateTime('fecha_envio_email')->nullable();
            $table->text('observaciones')->nullable();
            $table->foreignId('usuario_creador_id')->constrained('users');
            $table->string('usuario_creador_nombre');
            $table->timestamps();
            $table->softDeletes();

            $table->index('fecha_entrega');
            $table->index('user_id');
            $table->index('usuario_creador_id');
            $table->index('email_enviado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('constancias_credenciales');
    }
};
