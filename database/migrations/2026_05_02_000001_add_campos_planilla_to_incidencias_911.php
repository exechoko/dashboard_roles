<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCamposPlanillaToIncidencias911 extends Migration
{
    public function up()
    {
        Schema::table('incidencias_911', function (Blueprint $table) {
            // Minutos excedentes sobre el SLA (para cálculo de multa)
            // Existe en P01 (col 20) y P49 (col 21) con idéntico significado
            $table->decimal('minutos_exc', 14, 2)->default(0)->after('minutos_fallo');

            // Tercer nivel del pliego: "Por cámara", "Por puesto", "Por Terminales TETRA", etc.
            // Necesario para identificar la fila correcta en la Tabla de Ponderación
            $table->string('modulo_n3', 100)->default('')->after('modulo_n2');

            // Texto completo "Subsist. Donde se produjo inc." tal como viene de la planilla.
            // Formato: "Sist. CCTV - Módulo Monitoreo - Por cámara"
            // Permite reimportar sin parsear y mantener trazabilidad con el Excel.
            $table->string('subsistema_raw', 250)->nullable()->after('modulo_n3');

            // Equipo afectado — aparece desde P49 (col 30: "Terminal MDT-400", "HTT-500", etc.)
            // Null para incidencias de P01 que no registraban modelo.
            $table->string('equipo_modelo', 150)->nullable()->after('estado');

            // Período al que se IMPUTA la incidencia (puede diferir del período de análisis
            // cuando una persistente se arrastra). Ej: "P01", "Periodo 03".
            // En P01 estaba en col 14 ("Periodo Facturado"); en P49 en col 12 ("Per. Fact.").
            $table->string('periodo_facturado', 30)->nullable()->after('equipo_modelo');

            // Índice para búsquedas de importación por subsistema
            $table->index('subsistema_raw', 'incidencias_911_subsistema_idx');
        });
    }

    public function down()
    {
        Schema::table('incidencias_911', function (Blueprint $table) {
            $table->dropIndex('incidencias_911_subsistema_idx');
            $table->dropColumn([
                'minutos_exc',
                'modulo_n3',
                'subsistema_raw',
                'equipo_modelo',
                'periodo_facturado',
            ]);
        });
    }
}
