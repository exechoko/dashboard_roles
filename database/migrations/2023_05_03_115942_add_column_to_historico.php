<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnToHistorico extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('historico', function (Blueprint $table) {
            $table->string('recurso_desasignado')->nullable()->after('fecha_desasignacion');
            $table->string('vehiculo_desasignado')->nullable()->after('recurso_desasignado');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('historico', function (Blueprint $table) {
            $table->dropColumn('recurso_desasignado');
            $table->dropColumn('vehiculo_desasignado');
        });
    }
}
