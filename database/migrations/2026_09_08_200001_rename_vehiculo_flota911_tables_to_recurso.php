<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // estados_seccion
        if (!Schema::hasTable('recurso_estados_seccion')) {
            Schema::rename('vehiculo_estados_seccion', 'recurso_estados_seccion');
        }
        if (Schema::hasTable('recurso_estados_seccion') && Schema::hasColumn('recurso_estados_seccion', 'vehiculo_id')) {
            Schema::table('recurso_estados_seccion', function (Blueprint $table) {
                $table->renameColumn('vehiculo_id', 'recurso_id');
            });
        }

        // estado_diario
        if (!Schema::hasTable('recurso_estado_diario')) {
            Schema::rename('vehiculo_estado_diario', 'recurso_estado_diario');
        }
        if (Schema::hasTable('recurso_estado_diario') && Schema::hasColumn('recurso_estado_diario', 'vehiculo_id')) {
            Schema::table('recurso_estado_diario', function (Blueprint $table) {
                $table->renameColumn('vehiculo_id', 'recurso_id');
            });
        }

        // dotaciones
        if (!Schema::hasTable('recurso_dotaciones')) {
            Schema::rename('vehiculo_dotaciones', 'recurso_dotaciones');
        }
        if (Schema::hasTable('recurso_dotaciones') && Schema::hasColumn('recurso_dotaciones', 'vehiculo_id')) {
            Schema::table('recurso_dotaciones', function (Blueprint $table) {
                $table->renameColumn('vehiculo_id', 'recurso_id');
            });
        }

        // prestamos — renombrar tabla
        if (!Schema::hasTable('recurso_prestamos')) {
            Schema::rename('vehiculo_prestamos', 'recurso_prestamos');
        }
        // prestamos — renombrar columna (separado del addColumn)
        if (Schema::hasColumn('recurso_prestamos', 'vehiculo_id')) {
            Schema::table('recurso_prestamos', function (Blueprint $table) {
                $table->renameColumn('vehiculo_id', 'recurso_id');
            });
        }
        // prestamos — agregar snapshot (en operación separada)
        if (!Schema::hasColumn('recurso_prestamos', 'vehiculo_id_snapshot')) {
            Schema::table('recurso_prestamos', function (Blueprint $table) {
                $table->unsignedBigInteger('vehiculo_id_snapshot')->nullable()->after('recurso_id');
            });
        }

        // informe_preferencias
        if (!Schema::hasTable('recurso_informe_preferencias')) {
            Schema::rename('vehiculo_informe_preferencias', 'recurso_informe_preferencias');
        }
        if (Schema::hasTable('recurso_informe_preferencias') && Schema::hasColumn('recurso_informe_preferencias', 'vehiculo_ids')) {
            Schema::table('recurso_informe_preferencias', function (Blueprint $table) {
                $table->renameColumn('vehiculo_ids', 'recurso_ids');
            });
        }

        // novedades — renombrar tabla
        if (!Schema::hasTable('recurso_novedades')) {
            Schema::rename('vehiculo_novedades', 'recurso_novedades');
        }
        // novedades — renombrar columna
        if (Schema::hasColumn('recurso_novedades', 'vehiculo_id')) {
            Schema::table('recurso_novedades', function (Blueprint $table) {
                $table->renameColumn('vehiculo_id', 'recurso_id');
            });
        }
        // novedades — columnas nuevas (separadas)
        if (!Schema::hasColumn('recurso_novedades', 'tipo')) {
            Schema::table('recurso_novedades', function (Blueprint $table) {
                $table->string('tipo', 20)->default('operativa')->after('recurso_id');
            });
        }
        if (!Schema::hasColumn('recurso_novedades', 'vehiculo_id_referencia')) {
            Schema::table('recurso_novedades', function (Blueprint $table) {
                $table->unsignedBigInteger('vehiculo_id_referencia')->nullable()->after('tipo');
            });
        }

        // satélites
        if (!Schema::hasTable('recurso_novedad_seguimientos')) {
            Schema::rename('vehiculo_novedad_seguimientos', 'recurso_novedad_seguimientos');
        }
        if (!Schema::hasTable('recurso_novedad_adjuntos')) {
            Schema::rename('vehiculo_novedad_adjuntos', 'recurso_novedad_adjuntos');
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('vehiculo_novedad_adjuntos') && Schema::hasTable('recurso_novedad_adjuntos')) {
            Schema::rename('recurso_novedad_adjuntos', 'vehiculo_novedad_adjuntos');
        }
        if (!Schema::hasTable('vehiculo_novedad_seguimientos') && Schema::hasTable('recurso_novedad_seguimientos')) {
            Schema::rename('recurso_novedad_seguimientos', 'vehiculo_novedad_seguimientos');
        }

        if (Schema::hasColumn('recurso_novedades', 'vehiculo_id_referencia')) {
            Schema::table('recurso_novedades', fn(Blueprint $t) => $t->dropColumn('vehiculo_id_referencia'));
        }
        if (Schema::hasColumn('recurso_novedades', 'tipo')) {
            Schema::table('recurso_novedades', fn(Blueprint $t) => $t->dropColumn('tipo'));
        }
        if (Schema::hasColumn('recurso_novedades', 'recurso_id')) {
            Schema::table('recurso_novedades', fn(Blueprint $t) => $t->renameColumn('recurso_id', 'vehiculo_id'));
        }
        if (!Schema::hasTable('vehiculo_novedades') && Schema::hasTable('recurso_novedades')) {
            Schema::rename('recurso_novedades', 'vehiculo_novedades');
        }

        if (Schema::hasColumn('recurso_informe_preferencias', 'recurso_ids')) {
            Schema::table('recurso_informe_preferencias', fn(Blueprint $t) => $t->renameColumn('recurso_ids', 'vehiculo_ids'));
        }
        if (!Schema::hasTable('vehiculo_informe_preferencias') && Schema::hasTable('recurso_informe_preferencias')) {
            Schema::rename('recurso_informe_preferencias', 'vehiculo_informe_preferencias');
        }

        if (Schema::hasColumn('recurso_prestamos', 'vehiculo_id_snapshot')) {
            Schema::table('recurso_prestamos', fn(Blueprint $t) => $t->dropColumn('vehiculo_id_snapshot'));
        }
        if (Schema::hasColumn('recurso_prestamos', 'recurso_id')) {
            Schema::table('recurso_prestamos', fn(Blueprint $t) => $t->renameColumn('recurso_id', 'vehiculo_id'));
        }
        if (!Schema::hasTable('vehiculo_prestamos') && Schema::hasTable('recurso_prestamos')) {
            Schema::rename('recurso_prestamos', 'vehiculo_prestamos');
        }

        if (Schema::hasColumn('recurso_dotaciones', 'recurso_id')) {
            Schema::table('recurso_dotaciones', fn(Blueprint $t) => $t->renameColumn('recurso_id', 'vehiculo_id'));
        }
        if (!Schema::hasTable('vehiculo_dotaciones') && Schema::hasTable('recurso_dotaciones')) {
            Schema::rename('recurso_dotaciones', 'vehiculo_dotaciones');
        }

        if (Schema::hasColumn('recurso_estado_diario', 'recurso_id')) {
            Schema::table('recurso_estado_diario', fn(Blueprint $t) => $t->renameColumn('recurso_id', 'vehiculo_id'));
        }
        if (!Schema::hasTable('vehiculo_estado_diario') && Schema::hasTable('recurso_estado_diario')) {
            Schema::rename('recurso_estado_diario', 'vehiculo_estado_diario');
        }

        if (Schema::hasColumn('recurso_estados_seccion', 'recurso_id')) {
            Schema::table('recurso_estados_seccion', fn(Blueprint $t) => $t->renameColumn('recurso_id', 'vehiculo_id'));
        }
        if (!Schema::hasTable('vehiculo_estados_seccion') && Schema::hasTable('recurso_estados_seccion')) {
            Schema::rename('recurso_estados_seccion', 'vehiculo_estados_seccion');
        }
    }
};
