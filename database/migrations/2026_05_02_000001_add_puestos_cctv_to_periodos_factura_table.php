<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPuestosCctvToPeriodosFacturaTable extends Migration
{
    public function up()
    {
        Schema::table('periodos_factura', function (Blueprint $table) {
            $table->unsignedSmallInteger('n_total_puestos_cctv')->default(0)->after('n_total_camaras');
        });
    }

    public function down()
    {
        Schema::table('periodos_factura', function (Blueprint $table) {
            $table->dropColumn('n_total_puestos_cctv');
        });
    }
}