<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('activaciones_totem', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evento_cecoco_id')->unique()->constrained('evento_cecoco')->cascadeOnDelete();
            $table->string('nro_expediente', 20)->index();
            $table->dateTime('fecha_evento')->index();
            $table->string('palabra_detectada', 30);
            $table->string('estado', 20)->default('pendiente')->index();
            $table->foreignId('camara_id')->nullable()->constrained('camaras')->nullOnDelete();
            $table->foreignId('descargado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('fecha_descarga')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activaciones_totem');
    }
};
