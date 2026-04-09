<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTranscripcionJobsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transcripcion_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('audio_path');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->longText('result_text')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
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
        Schema::dropIfExists('transcripcion_jobs');
    }
}
