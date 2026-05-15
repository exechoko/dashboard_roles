<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexesToIncidencias911 extends Migration
{
    public function up()
    {
        Schema::table('incidencias_911', function (Blueprint $table) {
            // Único por período + código de incidencia: previene duplicados a nivel DB
            // y acelera el lookup en el import (where periodo_id=? and incidencia_code=?).
            $table->unique(['periodo_id', 'incidencia_code'], 'incidencias_911_periodo_codigo_unq');

            // Acelera arrastarPersistentes() y filtros por tipo dentro del período.
            $table->index(['periodo_id', 'tipo_incidencia'], 'incidencias_911_periodo_tipo_idx');

            // Acelera agrupaciones por sistema/módulo del análisis.
            $table->index(['sistema', 'modulo_n2'], 'incidencias_911_sistema_modulo_idx');
        });
    }

    public function down()
    {
        Schema::table('incidencias_911', function (Blueprint $table) {
            $table->dropUnique('incidencias_911_periodo_codigo_unq');
            $table->dropIndex('incidencias_911_periodo_tipo_idx');
            $table->dropIndex('incidencias_911_sistema_modulo_idx');
        });
    }
}
