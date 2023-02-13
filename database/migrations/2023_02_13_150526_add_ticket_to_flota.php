<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTicketToFlota extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('flota_general', function (Blueprint $table) {
            $table->string('ticket_per')->nullable()->after('fecha_desasignacion');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('flota_general', function (Blueprint $table) {
            $table->dropColumn('ticket_per');
        });
    }
}
