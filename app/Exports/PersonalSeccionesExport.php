<?php

namespace App\Exports;

use App\Models\PersonalSeccion;
use Illuminate\Support\Carbon;
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
     * Columnas opcionales, todas destildadas por defecto — el operador arma
     * la planilla a gusto según para qué la necesite. Las de fecha y las de
     * datos personales salen de `Personal`/personal911 (ya calculadas al
     * armar la fila); "firma" es una columna en blanco a propósito, para
     * imprimir y que cada funcionario firme a mano (notificaciones, etc.).
     *
     * @var array<string, string>
     */
    public const COLUMNAS_EXTRA = [
        'ingreso_division_911' => 'Fecha de ingreso a la división',
        'fecha_baja_seccion' => 'Fecha baja de sección',
        'dni' => 'DNI',
        'telefono' => 'Teléfono',
        'email' => 'Email',
        'fecha_nacimiento' => 'Fecha de Nac.',
        'edad' => 'Edad',
        'estado_civil' => 'Estado Civil',
        'direccion' => 'Domicilio',
        'situacion_personal911' => 'Situación',
        'observaciones' => 'Observaciones',
        'firma' => 'Firma',
    ];

    /**
     * @param  Collection<int, PersonalSeccion>  $registros
     * @param  list<string>  $columnasExtra  claves de self::COLUMNAS_EXTRA a incluir además de las base
     * @param  array<int, string|null>  $fechasIngreso911  personal911_id => Fec_Ing911, precalculado en lote (ver Personal911DetalleService::obtenerFechasIngreso911Masivo)
     */
    public function __construct(
        private Collection $registros,
        private array $columnasExtra = [],
        private array $fechasIngreso911 = []
    ) {
    }

    /**
     * Arma la fila completa (base + TODAS las columnas extra disponibles,
     * bajo la clave 'extra') a partir de un registro. La usan tanto el
     * Excel como la vista previa del modal, para no duplicar el mapeo.
     *
     * `$fechaIngreso911` viene de personal911 (Fec_Ing911) — NO es la fecha
     * de alta en esta sección puntual (eso `personal911` no lo trackea),
     * es el ingreso a la División 911 en general.
     *
     * @return array{nro: int, seccion: ?string, jerarquia: ?string, apellido: string, nombre: string, lp: ?string, funcion: ?string, estado: string, extra: array<string, string>}
     */
    public static function mapearFila(PersonalSeccion $r, int $nro, ?string $fechaIngreso911 = null): array
    {
        $p = $r->personal;
        $fechaIngreso911Valida = $fechaIngreso911 && !str_starts_with($fechaIngreso911, '0000')
            ? Carbon::parse($fechaIngreso911)->format('d/m/Y')
            : '';

        return [
            'nro' => $nro,
            'seccion' => $r->seccion,
            'jerarquia' => $p->jerarquia,
            'apellido' => $p->apellido,
            'nombre' => $p->nombre,
            'lp' => $p->lp,
            'funcion' => $r->funcion_actual,
            'estado' => $r->estadoLabel(),
            'extra' => [
                'ingreso_division_911' => $fechaIngreso911Valida,
                'fecha_baja_seccion' => optional($r->fecha_baja)->format('d/m/Y') ?? '',
                'dni' => (string) $p->dni,
                'telefono' => str_replace("\n", ' / ', (string) $p->telefono),
                'email' => (string) $p->email,
                'fecha_nacimiento' => optional($p->fecha_nacimiento)->format('d/m/Y') ?? '',
                'edad' => $p->edad !== null ? (string) $p->edad : '',
                'estado_civil' => (string) $p->estado_civil,
                'direccion' => (string) $p->direccion,
                'situacion_personal911' => (string) $p->situacion_personal911,
                'observaciones' => (string) $p->observaciones_personal911,
                'firma' => '',
            ],
        ];
    }

    public function collection()
    {
        $filas = $this->registros->values()->map(function (PersonalSeccion $r, int $key) {
            $fechaIngreso911 = $this->fechasIngreso911[$r->personal->personal911_id ?? 0] ?? null;
            $fila = self::mapearFila($r, $key + 1, $fechaIngreso911);
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
            'NRO', 'Sección', 'Jerarquía', 'Apellido', 'Nombre', 'L.P.', 'Función', 'Estado',
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
