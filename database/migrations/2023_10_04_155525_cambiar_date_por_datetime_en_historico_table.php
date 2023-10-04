<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CambiarDatePorDatetimeEnHistoricoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('historico', function (Blueprint $table) {
            $table->datetime('fecha_asignacion')->nullable()->change();
            $table->datetime('fecha_desasignacion')->nullable()->change();
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
            $table->date('fecha_asignacion')->nullable()->change();
            $table->date('fecha_desasignacion')->nullable()->change();
        });
    }
}
