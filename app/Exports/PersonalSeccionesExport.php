<?php

namespace App\Exports;

use App\Models\PersonalSeccion;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class PersonalSeccionesExport implements FromCollection, WithHeadings, WithEvents, ShouldAutoSize
{
    /**
     * @param  Collection<int, PersonalSeccion>  $registros
     */
    public function __construct(private Collection $registros)
    {
    }

    public function collection()
    {
        $filas = $this->registros->values()->map(function (PersonalSeccion $r, int $key) {
            $p = $r->personal;

            return [
                'nro' => $key + 1,
                'seccion' => $r->seccion,
                'jerarquia' => $p->jerarquia,
                'apellido' => $p->apellido,
                'nombre' => $p->nombre,
                'lp' => $p->lp,
                'funcion' => $r->funcion_actual,
                'estado' => $r->estadoLabel(),
                'fecha_alta' => optional($r->fecha_alta)->format('d/m/Y'),
                'fecha_baja' => optional($r->fecha_baja)->format('d/m/Y'),
            ];
        });

        return new Collection($filas);
    }

    public function headings(): array
    {
        return [
            'NRO', 'Sección', 'Jerarquía', 'Apellido', 'Nombre', 'L.P.',
            'Función', 'Estado', 'Fecha alta en sección', 'Fecha baja de sección',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $headerRange = 'A1:'.$sheet->getHighestColumn().'1';

                $sheet->getStyle($headerRange)->getFont()->setSize(12)->setBold(true);
                $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->setAutoFilter($headerRange);
                $sheet->freezePane('A2');
            },
        ];
    }
}
