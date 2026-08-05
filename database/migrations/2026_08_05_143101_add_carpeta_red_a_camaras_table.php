<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mapeo inicial camara_id => nombre exacto de subcarpeta bajo
     * \\193.169.1.247\totems\, por similitud con la captura de pantalla
     * provista. Es un punto de partida: se verifica/corrige desde la
     * pantalla "Configurar carpetas" del módulo Activaciones Tótem.
     */
    private const CARPETAS_INICIALES = [
        338 => 'General Galan y Jose Maria Paz',
        339 => 'Av. de las Americas y B. Racedo',
        340 => 'Crausaz y Espejo',
        341 => 'Batalla de Suipacha y Av. Don Bosco',
        342 => 'Ituzaingo y F. Sanchez',
        343 => 'Celia Torra y Escuela Lomas',
        344 => 'Artigas y Cochrane',
    ];

    public function up(): void
    {
        Schema::table('camaras', function (Blueprint $table) {
            $table->string('carpeta_red')->nullable()->after('tipo');
        });

        foreach (self::CARPETAS_INICIALES as $camaraId => $carpeta) {
            DB::table('camaras')->where('id', $camaraId)->update(['carpeta_red' => $carpeta]);
        }
    }

    public function down(): void
    {
        Schema::table('camaras', function (Blueprint $table) {
            $table->dropColumn('carpeta_red');
        });
    }
};
