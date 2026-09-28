<?php

namespace App\Exports;

use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class UsuariosExport implements FromCollection, WithHeadings, WithEvents, ShouldAutoSize
{
    public function __construct(
        private ?string $texto = null,
        private ?string $rol = null,
        private ?string $accesoExterno = null,
        private ?string $accesoPwa = null,
    ) {
    }

    /**
     * @return Collection
     */
    public function collection()
    {
        $texto = trim((string) $this->texto);

        $usuarios = User::query()
            ->when($texto !== '', function ($query) use ($texto) {
                $query->where(function ($query) use ($texto) {
                    $query->where('name', 'like', "%{$texto}%")
                        ->orWhere('apellido', 'like', "%{$texto}%")
                        ->orWhere('lp', 'like', "%{$texto}%")
                        ->orWhere('dni', 'like', "%{$texto}%")
                        ->orWhere('email', 'like', "%{$texto}%");
                });
            })
            ->when($this->rol, fn ($query) => $query->whereHas('roles', fn ($query) => $query->where('name', $this->rol)))
            ->when($this->accesoExterno !== null && $this->accesoExterno !== '', fn ($query) => $query->where('acceso_externo', $this->accesoExterno))
            ->when($this->accesoPwa !== null && $this->accesoPwa !== '', fn ($query) => $query->where('acceso_pwa', $this->accesoPwa))
            ->orderBy('apellido')
            ->orderBy('name')
            ->get();

        $numerados = $usuarios->map(function (User $usuario, int $key) {
            return [
                'nro' => $key + 1,
                'nombre' => $usuario->name,
                'apellido' => $usuario->apellido,
                'lp' => $usuario->lp,
                'dni' => $usuario->dni,
                'email' => $usuario->email,
                'roles' => $usuario->getRoleNames()->implode(', '),
                'acceso_externo' => $usuario->acceso_externo ? 'Sí' : 'No',
                'acceso_pwa' => $usuario->acceso_pwa ? 'Sí' : 'No',
            ];
        });

        return new Collection($numerados);
    }

    public function headings(): array
    {
        return [
            'NRO',
            'Nombre',
            'Apellido',
            'L.P.',
            'DNI',
            'E-mail',
            'Roles',
            'Acceso externo',
            'WebApp',
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
