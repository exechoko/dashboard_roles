<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function fkExists(string $table, string $constraintName): bool
    {
        return DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $constraintName)
            ->exists();
    }

    public function up(): void
    {
        // recurso_estados_seccion
        Schema::table('recurso_estados_seccion', function (Blueprint $table) {
            if ($this->fkExists('recurso_estados_seccion', 'vehiculo_estados_seccion_vehiculo_id_foreign')) {
                $table->dropForeign('vehiculo_estados_seccion_vehiculo_id_foreign');
            }
            if (!$this->fkExists('recurso_estados_seccion', 'recurso_estados_seccion_recurso_id_foreign')) {
                $table->foreign('recurso_id')->references('id')->on('recursos')->onDelete('cascade');
            }
        });

        // recurso_estado_diario
        Schema::table('recurso_estado_diario', function (Blueprint $table) {
            if ($this->fkExists('recurso_estado_diario', 'vehiculo_estado_diario_vehiculo_id_foreign')) {
                $table->dropForeign('vehiculo_estado_diario_vehiculo_id_foreign');
            }
            if (!$this->fkExists('recurso_estado_diario', 'recurso_estado_diario_recurso_id_foreign')) {
                $table->foreign('recurso_id')->references('id')->on('recursos')->onDelete('cascade');
            }
        });

        // recurso_dotaciones
        Schema::table('recurso_dotaciones', function (Blueprint $table) {
            if ($this->fkExists('recurso_dotaciones', 'vehiculo_dotaciones_vehiculo_id_foreign')) {
                $table->dropForeign('vehiculo_dotaciones_vehiculo_id_foreign');
            }
            if (!$this->fkExists('recurso_dotaciones', 'recurso_dotaciones_recurso_id_foreign')) {
                $table->foreign('recurso_id')->references('id')->on('recursos')->onDelete('cascade');
            }
        });

        // recurso_novedades
        Schema::table('recurso_novedades', function (Blueprint $table) {
            if ($this->fkExists('recurso_novedades', 'vehiculo_novedades_vehiculo_id_foreign')) {
                $table->dropForeign('vehiculo_novedades_vehiculo_id_foreign');
            }
            if (!$this->fkExists('recurso_novedades', 'recurso_novedades_recurso_id_foreign')) {
                $table->foreign('recurso_id')->references('id')->on('recursos')->onDelete('cascade');
            }
        });

        // recurso_prestamos
        Schema::table('recurso_prestamos', function (Blueprint $table) {
            if ($this->fkExists('recurso_prestamos', 'vehiculo_prestamos_vehiculo_id_foreign')) {
                $table->dropForeign('vehiculo_prestamos_vehiculo_id_foreign');
            }
            if (!$this->fkExists('recurso_prestamos', 'recurso_prestamos_recurso_id_foreign')) {
                $table->foreign('recurso_id')->references('id')->on('recursos')->onDelete('cascade');
            }
        });
    }

    public function down(): void
    {
        $tablas = [
            'recurso_prestamos'     => ['recurso_prestamos_recurso_id_foreign',    'vehiculo_prestamos_vehiculo_id_foreign'],
            'recurso_novedades'     => ['recurso_novedades_recurso_id_foreign',     'vehiculo_novedades_vehiculo_id_foreign'],
            'recurso_dotaciones'    => ['recurso_dotaciones_recurso_id_foreign',    'vehiculo_dotaciones_vehiculo_id_foreign'],
            'recurso_estado_diario' => ['recurso_estado_diario_recurso_id_foreign', 'vehiculo_estado_diario_vehiculo_id_foreign'],
            'recurso_estados_seccion' => ['recurso_estados_seccion_recurso_id_foreign', 'vehiculo_estados_seccion_vehiculo_id_foreign'],
        ];

        foreach ($tablas as $tabla => [$nuevaFk, $viejaFk]) {
            Schema::table($tabla, function (Blueprint $t) use ($tabla, $nuevaFk, $viejaFk) {
                if ($this->fkExists($tabla, $nuevaFk)) {
                    $t->dropForeign($nuevaFk);
                }
                if (!$this->fkExists($tabla, $viejaFk)) {
                    $t->foreign('recurso_id')->references('id')->on('vehiculos')->onDelete('cascade');
                }
            });
        }
    }
};
