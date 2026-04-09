<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRagConsultaJobsTable extends Migration
{
    public function up()
    {
        Schema::create('rag_consulta_jobs', function (Blueprint $table) {
            $table->id();
            $table->text('pregunta');
            $table->string('coleccion');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->text('respuesta')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('rag_consulta_jobs');
    }
}
