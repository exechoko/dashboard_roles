<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDependenciaToCamaras extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('camaras', function (Blueprint $table) {
            $table->integer('destino_id')->nullable()->after('sitio');
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
            $table->dropColumn('destino_id');
        });
    }
}
