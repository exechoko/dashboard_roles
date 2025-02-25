<?php

namespace App\Imports;

use App\Models\CamaraFisica;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;

class CamaraFisicaImport implements ToModel, WithStartRow
{
    public function model(array $row): ?CamaraFisica
    {
        // Convertir fecha de Excel (número serial o texto)
        $fechaRemito = null;

        try {
            // Si es un número serial de Excel
            if (is_numeric($row[4])) {
                $fechaRemito = Carbon::instance(
                    ExcelDate::excelToDateTimeObject($row[4])
                )->format('Y-m-d');
            }
            // Si es una cadena de texto (ej: "16/10/2021")
            else {
                $fechaRemito = Carbon::createFromFormat('d/m/Y', $row[4])->format('Y-m-d');
            }
        } catch (\Exception $e) {
            // Manejar errores (opcional: registrar el error)
            $fechaRemito = null;
        }

        return new CamaraFisica([
            'tipo_camara_id' => $row[0] ?? null,
            'numero_serie' => $row[1], // Campo único
            'estado' => $row[2] ?? 'disponible',
            'remito' => $row[3],
            'fecha_remito' => $fechaRemito,
            'observacion' => $row[5] ?? null,
        ]);
    }

    public function startRow(): int
    {
        return 2;
    }
}
