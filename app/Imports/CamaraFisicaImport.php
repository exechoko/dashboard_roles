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
        // Verificar si el número de serie ya existe
        if (CamaraFisica::where('numero_serie', $row[1])->exists()) {
            return null; // Evita agregar duplicados
        }

        // Convertir fecha de Excel (número serial o texto)
        $fechaRemito = null;
        try {
            if (is_numeric($row[4])) {
                $fechaRemito = Carbon::instance(
                    ExcelDate::excelToDateTimeObject($row[4])
                )->format('Y-m-d');
            } else {
                $fechaRemito = Carbon::createFromFormat('d/m/Y', $row[4])->format('Y-m-d');
            }
        } catch (\Exception $e) {
            $fechaRemito = null;
        }

        return new CamaraFisica([
            'tipo_camara_id' => $row[0] ?? null,
            'numero_serie' => $row[1],
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
