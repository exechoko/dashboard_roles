<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('personals', function (Blueprint $table) {
            $table->string('situacion_personal911', 100)->nullable()->after('jerarquia');
            $table->date('fecha_situacion_personal911')->nullable()->after('situacion_personal911');
            $table->string('funcion_personal911', 150)->nullable()->after('fecha_situacion_personal911');
            $table->text('observaciones_personal911')->nullable()->after('funcion_personal911');
            $table->index('situacion_personal911');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personals', function (Blueprint $table) {
            $table->dropIndex(['situacion_personal911']);
            $table->dropColumn([
                'situacion_personal911',
                'fecha_situacion_personal911',
                'funcion_personal911',
                'observaciones_personal911',
            ]);
        });
    }
};
