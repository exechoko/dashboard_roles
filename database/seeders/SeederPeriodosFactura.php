<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SeederPeriodosFactura extends Seeder
{
    public function run(): void
    {
        $base = Carbon::create(2022, 2, 18);
        $periodos = [];

        for ($i = 0; $i < 72; $i++) {
            $fechaInicio = $base->copy()->addMonths($i);
            $fechaFin    = $fechaInicio->copy()->addMonth()->subDay();
            $dias        = $fechaInicio->diffInDays($fechaFin) + 1;

            $periodos[] = [
                'numero'                 => $i + 1,
                'fecha_inicio'           => $fechaInicio->toDateString(),
                'fecha_fin'              => $fechaFin->toDateString(),
                'dias'                   => $dias,
                'minutos_totales'        => $dias * 24 * 60,
                'n_total_tetra'          => null,
                'n_total_camaras'        => null,
                'n_total_puestos_cecoco' => null,
                'factura_numero'         => null,
                'factura_monto'          => null,
                'expediente_numero'      => null,
                'ru_numero'              => null,
                'observaciones'          => null,
                'created_at'             => now(),
                'updated_at'             => now(),
            ];
        }

        // insertOrIgnore respeta registros existentes (no sobreescribe datos ya cargados)
        DB::table('periodos_factura')->insertOrIgnore($periodos);
    }
}
