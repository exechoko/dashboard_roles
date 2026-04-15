<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAudioTranscripcionesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('audio_transcripciones', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_archivo')->index();
            $table->string('telefono', 50)->nullable()->index();
            $table->string('tipo_emergencia')->nullable();
            $table->text('resumen')->nullable();
            $table->longText('transcripcion_json');
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
        Schema::dropIfExists('audio_transcripciones');
    }
}
