<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToHistorico extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('historico', function (Blueprint $table) {
            $table->string('recurso_asignado')->nullable()->after('destino_id');
            $table->string('vehiculo_asignado')->nullable()->after('recurso_asignado');
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
            $table->dropColumn('recurso_asignado');
            $table->dropColumn('vehiculo_asignado');
        });
    }
}
