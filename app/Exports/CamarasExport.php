<?php

namespace App\Exports;

use App\Models\Camara;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\BeforeExport;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet;

class CamarasExport implements FromCollection, WithHeadings, WithEvents, ShouldAutoSize
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
        //dd($camaras);

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

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                // Obtener la hoja de cálculo
                $sheet = $event->sheet->getDelegate();


                //Tamaño letra de cabecera
                $sheet->getStyle('A1:' . $event->sheet->getDelegate()->getHighestColumn() . '1')->getFont()->setSize(14);
                //Centrar Cabecera
                $sheet->getStyle('A1:' . $event->sheet->getDelegate()->getHighestColumn() . '1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                //Cabeceras en negrita
                $sheet->getStyle('A1:' . $event->sheet->getDelegate()->getHighestColumn() . '1')->getFont()->setBold(true);
                //Filtros en cabecera
                $sheet->setAutoFilter('A1:' . $event->sheet->getDelegate()->getHighestColumn() . '1');
                // Recorrer todas las columnas
                /*foreach ($sheet->getColumnIterator() as $column) {
                    // Establecer el ancho automático para cada columna
                    $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
                }*/
            }
        ];
    }
}
