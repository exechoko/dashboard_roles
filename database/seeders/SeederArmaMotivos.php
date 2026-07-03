<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SeederArmaMotivos extends Seeder
{
    public function run(): void
    {
        $motivos = [
            ['nombre' => '862', 'dias' => 0, 'tipo_asignado' => 'REGULACIÓN'],
            ['nombre' => '862/J.M.S', 'dias' => 1, 'tipo_asignado' => 'RETENCIÓN'],
            ['nombre' => 'A.R.T', 'dias' => 30, 'tipo_asignado' => 'RETENCIÓN'],
            ['nombre' => '106', 'dias' => 45, 'tipo_asignado' => 'RETENCIÓN'],
            ['nombre' => 'LICENCIA', 'dias' => 0, 'tipo_asignado' => 'RESGUARDO'],
            ['nombre' => 'J.M.S', 'dias' => 1, 'tipo_asignado' => 'RETENCIÓN'],
            ['nombre' => 'PSICOLÓGICO', 'dias' => 1, 'tipo_asignado' => 'RETENCIÓN'],
            ['nombre' => 'EMBARAZO', 'dias' => 0, 'tipo_asignado' => 'RESGUARDO'],
        ];

        foreach ($motivos as $motivo) {
            DB::table('arma_motivos')->updateOrInsert(
                ['nombre' => $motivo['nombre']],
                array_merge($motivo, ['activo' => true])
            );
        }

        $this->command->info('Motivos de retención de armas creados correctamente.');
    }
}
