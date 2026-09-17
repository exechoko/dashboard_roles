<?php

namespace App\Exports;

use App\Models\Antena;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class AntenasExport implements FromCollection, WithHeadings, WithEvents, ShouldAutoSize
{
    public function __construct(private ?string $texto = null)
    {
    }

    /**
     * @return Collection
     */
    public function collection()
    {
        $antenas = Antena::where('nombre', 'LIKE', '%' . $this->texto . '%')
            ->orWhere('localidad', 'LIKE', '%' . $this->texto . '%')
            ->orderBy('id', 'asc')
            ->get();

        $numeradas = $antenas->map(function (Antena $antena, int $key) {
            return [
                'nro' => $key + 1,
                'nombre' => $antena->nombre,
                'localidad' => $antena->localidad,
                'ubicacion' => $antena->ubicacion,
                'latitud' => $antena->latitud,
                'longitud' => $antena->longitud,
                'altura' => $antena->altura,
                'activa' => $antena->activa ? 'Activa' : 'Inactiva',
                'observaciones' => $antena->observaciones,
            ];
        });

        return new Collection($numeradas);
    }

    public function headings(): array
    {
        return [
            'NRO',
            'Nombre',
            'Localidad',
            'Ubicación',
            'Latitud',
            'Longitud',
            'Altura',
            'Estado',
            'Observaciones',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $headerRange = 'A1:' . $sheet->getHighestColumn() . '1';

                $sheet->getStyle($headerRange)->getFont()->setSize(14)->setBold(true);
                $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->setAutoFilter($headerRange);
                $sheet->freezePane('A2');
            },
        ];
    }
}
