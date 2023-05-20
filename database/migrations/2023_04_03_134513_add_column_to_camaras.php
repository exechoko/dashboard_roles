<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnToCamaras extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('camaras', function (Blueprint $table) {
            $table->string('marca')->nullable()->after('inteligencia');
            $table->string('modelo')->nullable()->after('marca');
            $table->string('nro_serie')->nullable()->after('modelo');
            $table->string('etapa')->nullable()->after('nro_serie');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('camaras', function (Blueprint $table) {
            $table->dropColumn('marca');
            $table->dropColumn('modelo');
            $table->dropColumn('nro_serie');
            $table->dropColumn('etapa');
        });
    }
}
