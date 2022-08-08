<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToEquipos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('equipos', function (Blueprint $table) {
            $table->boolean('gps')->nullable()->after('tei');
            $table->string('desc_gps')->nullable()->after('gps');
            $table->boolean('frente_remoto')->nullable()->after('desc_gps');
            $table->string('desc_frente')->nullable()->after('frente_remoto');
            $table->boolean('rf')->nullable()->after('desc_frente');
            $table->string('desc_rf')->nullable()->after('rf');
            $table->boolean('kit_inst')->nullable()->after('desc_rf');
            $table->string('desc_kit_inst')->nullable()->after('kit_inst');
            $table->boolean('operativo')->nullable()->after('desc_kit_inst');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('equipos', function (Blueprint $table) {
            $table->dropColumn('gps');
            $table->dropColumn('desc_gps');
            $table->dropColumn('frente_remoto');
            $table->dropColumn('desc_frente');
            $table->dropColumn('rf');
            $table->dropColumn('desc_rf');
            $table->dropColumn('kit_inst');
            $table->dropColumn('desc_kit_inst');
            $table->dropColumn('operativo');
        });
    }
}
