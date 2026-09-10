<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * El turno del parte diario de Flota 911 pasó de 07:00–19:00 / 19:00–07:00
 * a 06:15–18:15 / 18:15–06:15. Se renombran las claves de `horario`
 * (07_19 → 06_18, 19_07 → 18_06).
 *
 * `recurso_estado_diario.horario` y `recurso_dotaciones.horario` eran ENUM con
 * los valores viejos, así que primero se pasan a varchar (como partes_diarios)
 * y recién después se remapea la data.
 */
return new class extends Migration
{
    /** @var array<string, string> */
    private array $mapa = [
        '07_19' => '06_18',
        '19_07' => '18_06',
    ];

    /** @var list<string> */
    private array $tablas = ['partes_diarios', 'recurso_estado_diario', 'recurso_dotaciones'];

    public function up(): void
    {
        DB::statement("ALTER TABLE recurso_estado_diario MODIFY horario VARCHAR(20) NOT NULL DEFAULT '06_18'");
        DB::statement("ALTER TABLE recurso_dotaciones MODIFY horario VARCHAR(20) NOT NULL DEFAULT '06_18'");

        $this->reemplazar($this->mapa);
    }

    public function down(): void
    {
        $this->reemplazar(array_flip($this->mapa));

        DB::statement("ALTER TABLE recurso_estado_diario MODIFY horario ENUM('07_19','19_07') NOT NULL DEFAULT '07_19'");
        DB::statement("ALTER TABLE recurso_dotaciones MODIFY horario ENUM('07_19','19_07') NOT NULL DEFAULT '07_19'");
    }

    /** @param array<string, string> $mapa */
    private function reemplazar(array $mapa): void
    {
        foreach ($this->tablas as $tabla) {
            foreach ($mapa as $desde => $hacia) {
                DB::table($tabla)->where('horario', $desde)->update(['horario' => $hacia]);
            }
        }
    }
};
