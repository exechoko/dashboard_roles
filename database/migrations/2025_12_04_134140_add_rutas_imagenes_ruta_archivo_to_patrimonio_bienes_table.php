<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRutasImagenesRutaArchivoToPatrimonioBienesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('patrimonio_bienes', function (Blueprint $table) {
            $table->text('rutas_imagenes')->nullable()->after('observaciones');
            $table->text('ruta_archivo')->nullable()->after('rutas_imagenes');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('patrimonio_bienes', function (Blueprint $table) {
            $table->dropColumn('rutas_imagenes');
            $table->dropColumn('ruta_archivo');
        });
    }
}
