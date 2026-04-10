<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCallAnalysisJobsTable extends Migration
{
    public function up()
    {
        Schema::create('call_analysis_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('audio_path');
            $table->string('original_name')->nullable();
            $table->enum('mode', ['transcribe', 'analyze'])->default('analyze');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->longText('result_json')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('call_analysis_jobs');
    }
}
