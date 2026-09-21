<?php

namespace App\Imports\Concerns;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * La planilla legada de Dominios/Personas mezcla fechas como número de
 * serie de Excel (ej. 44797.79) y como texto "d/m/Y", según cómo haya
 * quedado tipeada la celda a lo largo de los años.
 */
trait ParseaFechaExcel
{
    private function parsearFecha(mixed $valor): ?string
    {
        $valor = trim((string) $valor);

        if ($valor === '' || $valor === '-') {
            return null;
        }

        if (is_numeric($valor)) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $valor)->format('Y-m-d');
            } catch (\Exception) {
                return null;
            }
        }

        foreach (['d/m/Y', 'd-m-Y', 'Y-m-d'] as $formato) {
            try {
                return Carbon::createFromFormat($formato, $valor)->format('Y-m-d');
            } catch (\Exception) {
                continue;
            }
        }

        try {
            return Carbon::parse($valor)->format('Y-m-d');
        } catch (\Exception) {
            return null;
        }
    }

    private function esActivo(mixed $valor): bool
    {
        $valor = strtoupper(trim((string) $valor));

        return $valor !== 'NO';
    }
}
