<?php

namespace App\Services;

use App\Models\SecuenciaTicketera;
use Illuminate\Support\Facades\DB;

class SecuenciaTicketeraService
{
    public function previsualizarCodigo(?int $anio = null): string
    {
        $anio = $anio ?? (int) now()->format('y');
        $ultimoNumero = (int) SecuenciaTicketera::query()
            ->where('anio', $anio)
            ->value('ultimo_numero');

        return $this->formatearCodigo($anio, $ultimoNumero + 1);
    }

    public function generarCodigo(?int $anio = null): string
    {
        $anio = $anio ?? (int) now()->format('y');

        return DB::transaction(function () use ($anio): string {
            $secuencia = SecuenciaTicketera::query()
                ->where('anio', $anio)
                ->lockForUpdate()
                ->first();

            if ($secuencia === null) {
                $secuencia = SecuenciaTicketera::create([
                    'anio'          => $anio,
                    'ultimo_numero' => 0,
                ]);
            }

            $secuencia->ultimo_numero++;
            $secuencia->save();

            return $this->formatearCodigo($anio, $secuencia->ultimo_numero);
        });
    }

    /**
     * Ajusta la secuencia de un año para que nunca quede por detrás del mayor
     * número ya usado (por ejemplo, tras importar el histórico desde Excel).
     */
    public function sincronizar(int $anio, int $ultimoNumero): void
    {
        DB::transaction(function () use ($anio, $ultimoNumero): void {
            $secuencia = SecuenciaTicketera::query()
                ->where('anio', $anio)
                ->lockForUpdate()
                ->first();

            if ($secuencia === null) {
                SecuenciaTicketera::create([
                    'anio'          => $anio,
                    'ultimo_numero' => $ultimoNumero,
                ]);

                return;
            }

            if ($ultimoNumero > $secuencia->ultimo_numero) {
                $secuencia->update(['ultimo_numero' => $ultimoNumero]);
            }
        });
    }

    private function formatearCodigo(int $anio, int $numero): string
    {
        return sprintf('PG/%02d-%03d', $anio, $numero);
    }
}
