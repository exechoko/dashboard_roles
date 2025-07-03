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
use Illuminate\Support\Collection;

class SitiosExport implements FromCollection, WithHeadings, WithEvents, ShouldAutoSize
{
    /**
    * @return Collection
    */
    public function collection()
    {
        $sitios = Sitio::select(
            'sitio.nombre',
            'sitio.cartel',
            'sitio.activo',
            'sitio.latitud',
            'sitio.longitud',
            'destino.nombre as dependencia',
            'sitio.localidad',
        )
        ->leftJoin('destino', 'sitio.destino_id', '=', 'destino.id')
        ->get();

        // Mapear la colección para cambiar los valores booleanos y agregar numeración
        $numeratedSitios = $sitios->map(function ($sitio, $key) {
            return [
                'nro' => $key + 1,  // Numeración secuencial comenzando en 1
                'nombre' => $sitio->nombre,
                'cartel' => $sitio->cartel ? 'SI' : 'NO',
                'activo' => $sitio->activo ? 'Activo' : 'Inactivo', // Convertir booleano a texto
                'latitud' => $sitio->latitud,
                'longitud' => $sitio->longitud,
                'dependencia' => $sitio->dependencia,
                'localidad' => $sitio->localidad,
            ];
        });

        return new Collection($numeratedSitios);
    }

    public function headings(): array
    {
        return [
            'NRO',
            'Nombre',
            'Cartel',
            'Activo',
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
                // Tamaño letra de cabecera
                $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->getFont()->setSize(14);
                // Centrar Cabecera
                $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                // Cabeceras en negrita
                $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->getFont()->setBold(true);
                // Filtros en cabecera
                $sheet->setAutoFilter('A1:' . $sheet->getHighestColumn() . '1');
            }
        ];
    }
}
