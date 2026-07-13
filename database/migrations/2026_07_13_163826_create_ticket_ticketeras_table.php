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
        Schema::create('tickets_ticketera', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incidencia_911_id')->nullable()->constrained('incidencias_911')->nullOnDelete();
            $table->string('codigo_interno', 20)->unique();
            $table->string('codigo_ticketera', 80)->nullable()->index();
            $table->string('url_seguimiento', 500)->nullable();
            $table->string('asunto', 200);
            $table->longText('texto_enviado');
            $table->string('tipo_equipo', 80)->nullable();
            $table->string('modelo_equipo', 80)->nullable();
            $table->string('movil', 80)->nullable();
            $table->string('dependencia', 160)->nullable();
            $table->string('problema_detectado', 250)->nullable();
            $table->string('prioridad', 40)->default('Alto');
            $table->string('subsistema', 250)->nullable();
            $table->string('periodo_facturado', 30)->nullable();
            $table->boolean('aplica_calculo')->default(true);
            $table->text('observaciones')->nullable();
            $table->string('estado_envio', 30)->default('borrador')->index();
            $table->string('estado_ticketera', 80)->nullable();
            $table->timestamp('enviado_en')->nullable();
            $table->text('ultimo_error')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets_ticketera');
    }
};
