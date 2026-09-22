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
     * Columnas opcionales que el operador puede sumar además de las base,
     * tanto en el Excel como en la vista previa. Todas ya están disponibles
     * localmente en `Personal` (sin consultar personal911 en vivo por fila).
     *
     * @var array<string, string>
     */
    public const COLUMNAS_EXTRA = [
        'dni' => 'DNI',
        'telefono' => 'Teléfono',
        'email' => 'Email',
        'fecha_nacimiento' => 'Fecha de Nac.',
        'edad' => 'Edad',
        'estado_civil' => 'Estado Civil',
        'direccion' => 'Domicilio',
        'situacion_personal911' => 'Situación',
        'observaciones' => 'Observaciones',
    ];

    /**
     * @param  Collection<int, PersonalSeccion>  $registros
     * @param  list<string>  $columnasExtra  claves de self::COLUMNAS_EXTRA a incluir además de las base
     */
    public function __construct(private Collection $registros, private array $columnasExtra = [])
    {
    }

    /**
     * Arma la fila completa (base + TODAS las columnas extra disponibles,
     * bajo la clave 'extra') a partir de un registro. La usan tanto el
     * Excel como la vista previa del modal, para no duplicar el mapeo.
     *
     * @return array{nro: int, seccion: ?string, jerarquia: ?string, apellido: string, nombre: string, lp: ?string, funcion: ?string, estado: string, fecha_alta: string, fecha_baja: string, extra: array<string, string>}
     */
    public static function mapearFila(PersonalSeccion $r, int $nro): array
    {
        $p = $r->personal;

        return [
            'nro' => $nro,
            'seccion' => $r->seccion,
            'jerarquia' => $p->jerarquia,
            'apellido' => $p->apellido,
            'nombre' => $p->nombre,
            'lp' => $p->lp,
            'funcion' => $r->funcion_actual,
            'estado' => $r->estadoLabel(),
            'fecha_alta' => optional($r->fecha_alta)->format('d/m/Y') ?? '',
            'fecha_baja' => optional($r->fecha_baja)->format('d/m/Y') ?? '',
            'extra' => [
                'dni' => (string) $p->dni,
                'telefono' => str_replace("\n", ' / ', (string) $p->telefono),
                'email' => (string) $p->email,
                'fecha_nacimiento' => optional($p->fecha_nacimiento)->format('d/m/Y') ?? '',
                'edad' => $p->edad !== null ? (string) $p->edad : '',
                'estado_civil' => (string) $p->estado_civil,
                'direccion' => (string) $p->direccion,
                'situacion_personal911' => (string) $p->situacion_personal911,
                'observaciones' => (string) $p->observaciones_personal911,
            ],
        ];
    }

    public function collection()
    {
        $filas = $this->registros->values()->map(function (PersonalSeccion $r, int $key) {
            $fila = self::mapearFila($r, $key + 1);
            $extra = $fila['extra'];
            unset($fila['extra']);

            foreach ($this->columnasExtra as $clave) {
                if (array_key_exists($clave, self::COLUMNAS_EXTRA)) {
                    $fila[$clave] = $extra[$clave] ?? '';
                }
            }

            return $fila;
        });

        return new Collection($filas);
    }

    public function headings(): array
    {
        $extra = array_values(array_intersect_key(self::COLUMNAS_EXTRA, array_flip($this->columnasExtra)));

        return array_merge([
            'NRO', 'Sección', 'Jerarquía', 'Apellido', 'Nombre', 'L.P.',
            'Función', 'Estado', 'Fecha alta en sección', 'Fecha baja de sección',
        ], $extra);
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
