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
     * la planilla a gusto según para qué la necesite. Cubren exactamente
     * los mismos datos que se ven al revisar el detalle de un funcionario
     * (Datos Personales / Datos Laborales / Estado Actual / Ingreso a
     * División 911), más un par propias del módulo. "firma" es una columna
     * en blanco a propósito, para imprimir y que cada funcionario firme a
     * mano (notificaciones, actas, etc.).
     *
     * @var array<string, string>
     */
    public const COLUMNAS_EXTRA = [
        // Datos Personales
        'dni' => 'DNI',
        'sexo' => 'Sexo',
        'grupo_sanguineo' => 'Grupo Sang.',
        'fecha_nacimiento' => 'Fecha de Nac.',
        'edad' => 'Edad',
        'estado_civil' => 'Estado Civil',
        'direccion' => 'Domicilio Actual',
        'telefono_1' => 'Teléfono 1',
        'telefono_2' => 'Teléfono 2',
        'cuil' => 'C.U.I.L. N°',
        'email' => 'Email',
        // Datos Laborales
        'fecha_ingreso_laboral' => 'Fecha Ingreso (laboral)',
        'legajo_contable' => 'Legajo Contable',
        'funcion_dp3' => 'Función D.P.3',
        'cuerpo' => 'Cuerpo',
        'tipo_arma' => 'Tipo de Arma',
        'numero_arma' => 'N° Arma',
        'domicilio_laboral' => 'Domicilio Laboral',
        'observaciones' => 'Observaciones',
        // Estado Actual
        'situacion_personal911' => 'Situación',
        'fecha_situacion' => 'Fecha de Situación',
        'norma_estado' => 'Norma Res./Dec. (Estado)',
        // Ingreso a División 911 y V.V.
        'ingreso_division_911' => 'Fecha de ingreso a la división',
        'norma_ingreso_division' => 'Norma Res./Dec. (Ingreso Div.)',
        // Sección (propio del módulo, no viene de personal911)
        'fecha_baja_seccion' => 'Fecha baja de sección',
        // Otros
        'firma' => 'Firma',
    ];

    /**
     * Mismas claves que COLUMNAS_EXTRA, agrupadas para mostrar los
     * checkboxes organizados igual que las secciones del detalle del
     * funcionario. Solo para la UI (el Excel no usa esta agrupación).
     *
     * @var array<string, list<string>>
     */
    public const GRUPOS = [
        'Datos Personales' => ['dni', 'sexo', 'grupo_sanguineo', 'fecha_nacimiento', 'edad', 'estado_civil', 'direccion', 'telefono_1', 'telefono_2', 'cuil', 'email'],
        'Datos Laborales' => ['fecha_ingreso_laboral', 'legajo_contable', 'funcion_dp3', 'cuerpo', 'tipo_arma', 'numero_arma', 'domicilio_laboral', 'observaciones'],
        'Estado Actual' => ['situacion_personal911', 'fecha_situacion', 'norma_estado'],
        'Ingreso a División 911' => ['ingreso_division_911', 'norma_ingreso_division'],
        'Sección' => ['fecha_baja_seccion'],
        'Otros' => ['firma'],
    ];

    /**
     * @param  Collection<int, PersonalSeccion>  $registros
     * @param  list<string>  $columnasExtra  claves de self::COLUMNAS_EXTRA a incluir además de las base
     * @param  array<int, object>  $detalles911  personal911_id => fila de Personal911DetalleService::obtenerMasivo()
     */
    public function __construct(
        private Collection $registros,
        private array $columnasExtra = [],
        private array $detalles911 = []
    ) {
    }

    /**
     * Arma la fila completa (base + TODAS las columnas extra disponibles,
     * bajo la clave 'extra') a partir de un registro. La usan tanto el
     * Excel como la vista previa del modal, para no duplicar el mapeo.
     *
     * `$detalle911` (fila de Personal911DetalleService::obtener()/obtenerMasivo())
     * puede venir null si el funcionario no tiene personal911_id o la
     * conexión falló — todos los campos que dependen de él quedan vacíos
     * en ese caso, sin romper la fila.
     *
     * @return array{nro: int, seccion: ?string, jerarquia: ?string, apellido: string, nombre: string, lp: ?string, funcion: ?string, estado: string, extra: array<string, string>}
     */
    public static function mapearFila(PersonalSeccion $r, int $nro, ?object $detalle911 = null): array
    {
        $p = $r->personal;
        $fecha = fn ($valor) => $valor && !str_starts_with((string) $valor, '0000')
            ? Carbon::parse($valor)->format('d/m/Y')
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
                'dni' => (string) ($detalle911->Doc_Func ?? $p->dni ?? ''),
                'sexo' => (string) ($detalle911->Nombre_SexoFunc ?? ''),
                'grupo_sanguineo' => (string) ($detalle911->Nom_GrupoSang ?? ''),
                'fecha_nacimiento' => $fecha($p->fecha_nacimiento),
                'edad' => $p->edad !== null ? (string) $p->edad : '',
                'estado_civil' => (string) ($detalle911->Nom_ECivil ?? $p->estado_civil ?? ''),
                'direccion' => (string) ($detalle911->Dom_Func ?? $p->direccion ?? ''),
                'telefono_1' => (string) ($detalle911->Telefono1_Func ?? ''),
                'telefono_2' => (string) ($detalle911->Telefono2_Func ?? ''),
                'cuil' => (string) ($detalle911->Cuil_Func ?? ''),
                'email' => (string) ($detalle911->Email_Func ?? $p->email ?? ''),
                'fecha_ingreso_laboral' => $fecha($detalle911->FecIng_Func ?? null),
                'legajo_contable' => (string) ($detalle911->LgjC_Func ?? ''),
                'funcion_dp3' => (string) ($detalle911->funcion_dp3 ?? ''),
                'cuerpo' => (string) ($detalle911->Nom_Cuerpo ?? ''),
                'tipo_arma' => (string) ($detalle911->Nombre_TipoArma ?? ''),
                'numero_arma' => (string) ($p->numeracion_arma ?? ''),
                'domicilio_laboral' => (string) ($detalle911->Nombre_DomLab ?? ''),
                'observaciones' => (string) $p->observaciones_personal911,
                'situacion_personal911' => (string) $p->situacion_personal911,
                'fecha_situacion' => $fecha($p->fecha_situacion_personal911),
                'norma_estado' => (string) ($detalle911->Obs_Estado ?? ''),
                'ingreso_division_911' => $fecha($detalle911->Fec_Ing911 ?? null),
                'norma_ingreso_division' => (string) ($detalle911->Norma_Ing911 ?? ''),
                'fecha_baja_seccion' => optional($r->fecha_baja)->format('d/m/Y') ?? '',
                'firma' => '',
            ],
        ];
    }

    public function collection()
    {
        $filas = $this->registros->values()->map(function (PersonalSeccion $r, int $key) {
            $detalle911 = $this->detalles911[$r->personal->personal911_id ?? 0] ?? null;
            $fila = self::mapearFila($r, $key + 1, $detalle911);
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
