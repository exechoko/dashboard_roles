<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('personal_arma_asignaciones', function (Blueprint $table) {
            $table->boolean('activa')->nullable()->default(true)->after('fecha_hasta');
            $table->unique(['personal_id', 'activa'], 'personal_arma_activa_unique');
            $table->unique(['arma_id', 'activa'], 'arma_asignacion_activa_unique');
        });

        Schema::table('personal_chaleco_asignaciones', function (Blueprint $table) {
            $table->boolean('activa')->nullable()->default(true)->after('fecha_hasta');
            $table->unique(['personal_id', 'activa'], 'personal_chaleco_activo_unique');
            $table->unique(['chaleco_id', 'activa'], 'chaleco_asignacion_activa_unique');
        });

        Schema::table('arma_retenciones', function (Blueprint $table) {
            $table->string('arma_numero', 50)->nullable()->after('chaleco_id');
            $table->string('arma_tipo', 100)->nullable()->after('arma_numero');
            $table->string('chaleco_numero', 50)->nullable()->after('arma_tipo');
            $table->string('chaleco_detalle', 255)->nullable()->after('chaleco_numero');
        });

        DB::table('arma_retenciones as ar')
            ->leftJoin('armas as a', 'a.id', '=', 'ar.arma_id')
            ->leftJoin('arma_tipos as at', 'at.id', '=', 'a.arma_tipo_id')
            ->leftJoin('chalecos as c', 'c.id', '=', 'ar.chaleco_id')
            ->select(['ar.id', 'a.numero as arma_numero', 'at.nombre as arma_tipo', 'c.numero_serie as chaleco_numero', 'c.marca', 'c.modelo', 'c.talle', 'c.nivel'])
            ->orderBy('ar.id')
            ->each(function (object $retencion): void {
                $detalleChaleco = collect([
                    $retencion->marca,
                    $retencion->modelo,
                    $retencion->talle ? 'Talle '.$retencion->talle : null,
                    $retencion->nivel,
                ])->filter()->implode(' - ');

                DB::table('arma_retenciones')->where('id', $retencion->id)->update([
                    'arma_numero' => $retencion->arma_numero,
                    'arma_tipo' => $retencion->arma_tipo,
                    'chaleco_numero' => $retencion->chaleco_numero,
                    'chaleco_detalle' => $detalleChaleco !== '' ? $detalleChaleco : null,
                ]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('arma_retenciones', function (Blueprint $table) {
            $table->dropColumn(['arma_numero', 'arma_tipo', 'chaleco_numero', 'chaleco_detalle']);
        });

        Schema::table('personal_chaleco_asignaciones', function (Blueprint $table) {
            $table->dropUnique('personal_chaleco_activo_unique');
            $table->dropUnique('chaleco_asignacion_activa_unique');
            $table->dropColumn('activa');
        });

        Schema::table('personal_arma_asignaciones', function (Blueprint $table) {
            $table->dropUnique('personal_arma_activa_unique');
            $table->dropUnique('arma_asignacion_activa_unique');
            $table->dropColumn('activa');
        });
    }
};
