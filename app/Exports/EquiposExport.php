<?php

namespace App\Exports;

use App\Models\Equipo;
use App\Models\FlotaGeneral;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\BeforeExport;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet;

class EquiposExport implements FromCollection, WithHeadings, WithEvents, ShouldAutoSize
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $equipos = Equipo::select(
            'equipos.id',
            DB::raw("CONCAT(tipo_terminales.marca, ' ', tipo_terminales.modelo) AS terminal"),
            'estados.nombre as estado',
            'equipos.tei as tei',
            'equipos.issi as issi',
            'equipos.nombre_issi as id_issi',
            'equipos.provisto as provisto',
            'recursos.nombre as recurso',
            'destino.nombre as dependencia',
            //'flota_general.id as flota_id',
        )
            ->leftJoin('flota_general', 'equipos.id', '=', 'flota_general.equipo_id')
            ->leftJoin('recursos', 'flota_general.recurso_id', '=', 'recursos.id')
            ->leftJoin('destino', 'flota_general.destino_id', '=', 'destino.id')
            ->leftJoin('tipo_terminales', 'equipos.tipo_terminal_id', '=', 'tipo_terminales.id')
            ->leftJoin('estados', 'equipos.estado_id', '=', 'estados.id')
            ->get();

        return $equipos;
    }

    public function headings(): array
    {
        return [
            'Nro',
            'Terminal',
            'Estado',
            'TEI',
            'ISSI',
            'ID ISSI',
            'Provisto',
            'Recurso',
            'Dependencia'
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                // Tamaño letra de cabecera
                $event->sheet->getStyle('A1:I1')->getFont()->setSize(14);
                // Centrar Cabecera
                $event->sheet->getStyle('A1:I1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                // Cabeceras en negrita
                $event->sheet->getStyle('A1:I1')->getFont()->setBold(true);
                // Filtros en cabecera
                $event->sheet->setAutoFilter('A1:I1');
            },
        ];
    }
}
