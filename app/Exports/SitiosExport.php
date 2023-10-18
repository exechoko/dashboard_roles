<?php

namespace App\Exports;

use App\Models\Sitio;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\BeforeExport;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet;

class SitiosExport implements FromCollection, WithHeadings, WithEvents, ShouldAutoSize
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $sitios = Sitio::select(
            'sitio.id',
            'sitio.nombre',
            'sitio.latitud',
            'sitio.longitud',
            'destino.nombre as dependencia',
            'sitio.localidad',
        )
        ->leftJoin('destino', 'sitio.destino_id', '=', 'destino.id')
        ->get();
        return $sitios;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nombre',
            'Latitud',
            'Longitud',
            'Dependencia',
            'Localidad',
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
            }
        ];
    }
}
