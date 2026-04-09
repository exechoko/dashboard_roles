<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRagCargaJobsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rag_carga_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('archivo_path');           // ruta en storage
            $table->string('archivo_nombre');         // nombre original
            $table->string('coleccion');              // temática destino
            $table->boolean('resumir')->default(true);
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->text('resumen')->nullable();
            $table->unsignedInteger('documentos_total')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('rag_carga_jobs');
    }
}
