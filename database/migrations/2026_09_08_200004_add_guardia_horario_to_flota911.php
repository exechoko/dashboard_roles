<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('recurso_dotaciones')->truncate();
        DB::table('recurso_estado_diario')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        Schema::table('recurso_estado_diario', function (Blueprint $table) {
            $table->enum('guardia', ['guardia_1', 'guardia_2', 'guardia_3', 'guardia_4'])->nullable()->after('recurso_id');
            $table->enum('horario', ['07_19', '19_07'])->default('07_19')->after('guardia');
            $table->dateTime('fecha_inicio')->nullable()->after('horario');
            $table->dateTime('fecha_fin')->nullable()->after('fecha_inicio');
        });

        Schema::table('recurso_estado_diario', function (Blueprint $table) {
            $table->unique(['recurso_id', 'fecha_inicio'], 'recurso_estado_diario_recurso_inicio_unique');
            $table->index(['recurso_id', 'fecha_inicio'], 'recurso_estado_diario_recurso_inicio_index');
        });

        $this->dropIndexIfExists('recurso_estado_diario', 'vehiculo_estado_diario_vehiculo_id_fecha_unique');
        $this->dropIndexIfExists('recurso_estado_diario', 'vehiculo_estado_diario_vehiculo_id_fecha_index');

        Schema::table('recurso_estado_diario', function (Blueprint $table) {
            $table->dropColumn('fecha');
        });

        Schema::table('recurso_dotaciones', function (Blueprint $table) {
            $table->enum('guardia', ['guardia_1', 'guardia_2', 'guardia_3', 'guardia_4'])->nullable()->after('personal_id');
            $table->enum('horario', ['07_19', '19_07'])->default('07_19')->after('guardia');
            $table->dateTime('fecha_inicio')->nullable()->after('horario');
            $table->dateTime('fecha_fin')->nullable()->after('fecha_inicio');
        });

        Schema::table('recurso_dotaciones', function (Blueprint $table) {
            $table->unique(['recurso_id', 'personal_id', 'fecha_inicio'], 'recurso_dotaciones_recurso_personal_inicio_unique');
            $table->index(['recurso_id', 'fecha_inicio'], 'recurso_dotaciones_recurso_inicio_index');
        });

        $this->dropIndexIfExists('recurso_dotaciones', 'vehiculo_dotaciones_vehiculo_id_personal_id_fecha_unique');
        $this->dropIndexIfExists('recurso_dotaciones', 'vehiculo_dotaciones_vehiculo_id_fecha_index');

        Schema::table('recurso_dotaciones', function (Blueprint $table) {
            $table->dropColumn('fecha');
        });
    }

    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('recurso_dotaciones')->truncate();
        DB::table('recurso_estado_diario')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        Schema::table('recurso_estado_diario', function (Blueprint $table) {
            $table->date('fecha')->after('recurso_id');
        });
        Schema::table('recurso_estado_diario', function (Blueprint $table) {
            $table->unique(['recurso_id', 'fecha'], 'vehiculo_estado_diario_vehiculo_id_fecha_unique');
            $table->index(['recurso_id', 'fecha'], 'vehiculo_estado_diario_vehiculo_id_fecha_index');
        });
        $this->dropIndexIfExists('recurso_estado_diario', 'recurso_estado_diario_recurso_inicio_unique');
        $this->dropIndexIfExists('recurso_estado_diario', 'recurso_estado_diario_recurso_inicio_index');
        Schema::table('recurso_estado_diario', function (Blueprint $table) {
            $table->dropColumn(['guardia', 'horario', 'fecha_inicio', 'fecha_fin']);
        });

        Schema::table('recurso_dotaciones', function (Blueprint $table) {
            $table->date('fecha')->after('personal_id');
        });
        Schema::table('recurso_dotaciones', function (Blueprint $table) {
            $table->unique(['recurso_id', 'personal_id', 'fecha'], 'vehiculo_dotaciones_vehiculo_id_personal_id_fecha_unique');
            $table->index(['recurso_id', 'fecha'], 'vehiculo_dotaciones_vehiculo_id_fecha_index');
        });
        $this->dropIndexIfExists('recurso_dotaciones', 'recurso_dotaciones_recurso_personal_inicio_unique');
        $this->dropIndexIfExists('recurso_dotaciones', 'recurso_dotaciones_recurso_inicio_index');
        Schema::table('recurso_dotaciones', function (Blueprint $table) {
            $table->dropColumn(['guardia', 'horario', 'fecha_inicio', 'fecha_fin']);
        });
    }

    private function dropIndexIfExists(string $table, string $index): void
    {
        $exists = DB::selectOne(
            'SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1',
            [$table, $index]
        );

        if ($exists) {
            DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$index}`");
        }
    }
};
