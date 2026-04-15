<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFilenameTelefonoToTranscripcionJobsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transcripcion_jobs', function (Blueprint $table) {
            $table->string('original_filename')->nullable()->after('audio_path');
            $table->string('telefono', 50)->nullable()->after('original_filename');
            $table->longText('result_json')->nullable()->after('result_text');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transcripcion_jobs', function (Blueprint $table) {
            $table->dropColumn(['original_filename', 'telefono', 'result_json']);
        });
    }
}
