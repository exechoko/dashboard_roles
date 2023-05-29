<?php

namespace App\Exports;

use App\Models\Camara;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Facades\Excel;

class CamarasExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Camara::select('id', 'nombre', 'sitio', 'inteligencia', 'marca', 'modelo', 'etapa', 'latitud', 'longitud', 'fecha_instalacion')->get();
        //return Camara::all();
    }

    public function headings(): array
    {
        return [
            'Nro',
            'Nombre',
            'Sitio',
            'Inteligencia',
            'Marca',
            'Modelo',
            'Etapa',
            'Latitud',
            'Longitud',
            'Fecha de Instalación',
        ];
    }
}
