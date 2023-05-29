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
        //return Camara::select('id', 'nombre', 'sitio', 'inteligencia', 'marca', 'modelo', 'etapa', 'latitud', 'longitud', 'fecha_instalacion')->get();
        $camaras = Camara::select(
            'camaras.id',
            'camaras.nombre',
            'camaras.sitio',
            'camaras.inteligencia',
            'tipo_camara.tipo as tipo_camara',
            'tipo_camara.marca as marca',
            'tipo_camara.modelo as modelo',
            'camaras.etapa',
            'camaras.latitud',
            'camaras.longitud',
            'camaras.fecha_instalacion',
            'destino.nombre as dependencia'
        )
        ->leftJoin('tipo_camara', 'camaras.tipo_camara_id', '=', 'tipo_camara.id')
        ->leftJoin('destino', 'camaras.destino_id', '=', 'destino.id')
        ->get();
        return $camaras;
        //return Camara::all();
    }

    public function headings(): array
    {
        return [
            'Nro',
            'Nombre',
            'Sitio',
            'Inteligencia',
            'Tipo',
            'Marca',
            'Modelo',
            'Etapa',
            'Latitud',
            'Longitud',
            'Fecha de Instalación',
            'Dependencia',
        ];
    }
}
