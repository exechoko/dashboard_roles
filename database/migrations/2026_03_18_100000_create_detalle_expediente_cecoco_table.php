<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('detalle_expediente_cecoco', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evento_cecoco_id')->unique()->constrained('evento_cecoco')->cascadeOnDelete();
            $table->string('nro_expediente', 20)->index();
            $table->json('detalle_json');
            $table->dateTime('fecha_consulta');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_expediente_cecoco');
    }
};
