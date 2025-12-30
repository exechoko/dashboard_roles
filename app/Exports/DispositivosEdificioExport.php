<?php

namespace App\Exports;

use App\Models\DispositivoEdificio;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;

class DispositivosEdificioExport implements FromCollection, WithHeadings, WithEvents, ShouldAutoSize
{
    protected array $filters;

    protected bool $canCredentials;

    public function __construct(array $filters = [], bool $canCredentials = false)
    {
        $this->filters = $this->normalizeFilters($filters);
        $this->canCredentials = $canCredentials;
    }

    private function normalizeFilters(array $filters): array
    {
        return [
            'tipos' => (array) ($filters['tipos'] ?? []),
            'oficina' => isset($filters['oficina']) ? trim((string) $filters['oficina']) : null,
            'piso' => isset($filters['piso']) ? trim((string) $filters['piso']) : null,
            'activo' => $filters['activo'] ?? null,
        ];
    }

    public function collection()
    {
        $query = DispositivoEdificio::query()->orderBy('tipo')->orderBy('nombre');

        if (!empty($this->filters['tipos'])) {
            $query->whereIn('tipo', $this->filters['tipos']);
        }

        if (!empty($this->filters['oficina'])) {
            $query->porOficina($this->filters['oficina']);
        }

        if (!empty($this->filters['piso'])) {
            $query->porPiso($this->filters['piso']);
        }

        if ($this->filters['activo'] !== null && $this->filters['activo'] !== '') {
            $activo = $this->filters['activo'];
            if ($activo === true || $activo === 1 || $activo === '1' || $activo === 'true') {
                $query->activos();
            } elseif ($activo === false || $activo === 0 || $activo === '0' || $activo === 'false') {
                $query->where('activo', false);
            }
        }

        $devices = $query->get();

        $numerated = $devices->map(function (DispositivoEdificio $device, int $idx) {
            return [
                'nro' => $idx + 1,
                'tipo' => $device->tipo_label,
                'nombre' => $device->nombre,
                'ip' => $device->ip,
                'mac' => $device->mac,
                'marca' => $device->marca,
                'modelo' => $device->modelo,
                'serie' => $device->serie,
                'oficina' => $device->oficina,
                'piso' => $device->piso,
                'posicion_x' => $device->posicion_x,
                'posicion_y' => $device->posicion_y,
                'sistema_operativo' => $device->sistema_operativo,
                'puertos' => $device->puertos,
                'activo' => $device->activo ? 'Activo' : 'Inactivo',
                'observaciones' => $device->observaciones,
                'tiene_credenciales' => $device->tieneCredenciales() ? 'SI' : 'NO',
                'username' => $this->canCredentials ? $device->username : null,
                'password' => $this->canCredentials ? $device->password : null,
                'created_at' => $device->created_at ? $device->created_at->format('d/m/Y H:i') : null,
                'updated_at' => $device->updated_at ? $device->updated_at->format('d/m/Y H:i') : null,
            ];
        });

        return new Collection($numerated);
    }

    public function headings(): array
    {
        return [
            'Nro',
            'Tipo',
            'Nombre',
            'IP',
            'MAC',
            'Marca',
            'Modelo',
            'Serie',
            'Oficina',
            'Piso',
            'Posición X',
            'Posición Y',
            'Sistema Operativo',
            'Puertos',
            'Activo',
            'Observaciones',
            'Tiene credenciales',
            'Usuario',
            'Contraseña',
            'Creado',
            'Actualizado',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $headerRange = 'A1:' . $sheet->getHighestColumn() . '1';
                $sheet->getStyle($headerRange)->getFont()->setSize(14);
                $sheet->getStyle($headerRange)->getFont()->setBold(true);
                $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->setAutoFilter($headerRange);
                $sheet->freezePane('A2');
            },
        ];
    }
}
