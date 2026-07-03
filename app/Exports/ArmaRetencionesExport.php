<?php

namespace App\Exports;

use App\Models\ArmaRetencion;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;

class ArmaRetencionesExport implements FromCollection, WithHeadings, ShouldAutoSize, WithMapping, WithEvents
{
    protected $filters;
    protected $rowNumber = 0;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = ArmaRetencion::with(['personal', 'motivo']);

        if (!empty($this->filters['estado'])) {
            $query->where('estado', $this->filters['estado']);
        }

        if (!empty($this->filters['tipo'])) {
            $query->where('tipo', $this->filters['tipo']);
        }

        if (!empty($this->filters['motivo_id'])) {
            $query->where('motivo_id', $this->filters['motivo_id']);
        }

        if (!empty($this->filters['busqueda'])) {
            $busqueda = $this->filters['busqueda'];
            $query->where(function ($q) use ($busqueda) {
                $q->whereHas('personal', function ($q2) use ($busqueda) {
                    $q2->where('nombre', 'like', "%{$busqueda}%")
                       ->orWhere('apellido', 'like', "%{$busqueda}%")
                       ->orWhere('lp', 'like', "%{$busqueda}%");
                })->orWhere('numeracion_arma', 'like', "%{$busqueda}%");
            });
        }

        return $query->orderByDesc('fecha_posesion')->get();
    }

    public function map($retencion): array
    {
        $this->rowNumber++;

        return [
            $this->rowNumber,
            $retencion->personal->nombre_completo ?? '',
            $retencion->personal->lp ?? '',
            $retencion->personal->jerarquia ?? '',
            $retencion->numeracion_arma,
            $retencion->nro_chaleco ?? '',
            $retencion->tipo_label,
            $retencion->motivo->nombre ?? '',
            $retencion->fecha_posesion->format('d/m/Y'),
            $retencion->dias_restantes ?? '',
            $retencion->fecha_elevacion ? $retencion->fecha_elevacion->format('d/m/Y') : '',
            $retencion->fecha_devolucion ? $retencion->fecha_devolucion->format('d/m/Y') : '',
            $retencion->estado_label,
            $retencion->observaciones ?? '',
        ];
    }

    public function headings(): array
    {
        return [
            'Nro',
            'Funcionario',
            'Legajo',
            'Jerarquía',
            'Numeración Arma',
            'Nro Chaleco',
            'Tipo',
            'Motivo',
            'Fecha Posesión',
            'Días Restantes',
            'Fecha Elevación',
            'Fecha Devolución',
            'Estado',
            'Observaciones',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $lastColumn = $event->sheet->getHighestColumn();
                $headerRange = 'A1:' . $lastColumn . '1';

                $event->sheet->getStyle($headerRange)->getFont()->setSize(12);
                $event->sheet->getStyle($headerRange)->getFont()->setBold(true);
                $event->sheet->getStyle($headerRange)->getAlignment()->setHorizontal(
                    \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
                );

                $event->sheet->setAutoFilter($headerRange);
                $event->sheet->freezePane('A2');
            },
        ];
    }
}
