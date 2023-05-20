<?php

namespace App\Imports;

use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;
use App\Models\Camara;

class CamaraImport implements ToModel,WithStartRow
{
    public function model(array $row): Model
    {
        //dd($row[2]);
        return new Camara([
            'nombre' => $row[0],
            'ip' => $row[1],
            'tipo' => $row[2],
            'inteligencia' => $row[3],
            'marca' => $row[4],
            'modelo' => $row[5],
            'nro_serie' => $row[6],
            'etapa' => $row[7],
            'sitio' => $row[8],
            'latitud' => $row[9],
            'longitud' => $row[10],
        ]);
    }

    public function startRow(): int {
        return 2;
    }
}
