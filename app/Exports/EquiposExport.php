<?php

namespace App\Exports;

use App\Models\Destino;
use App\Models\Equipo;
use App\Models\FlotaGeneral;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\BeforeExport;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet;

class EquiposExport implements FromCollection, WithHeadings, WithEvents, ShouldAutoSize, WithMapping
{
    protected $rowNumber = 0;

    protected $filters = [];

    public function __construct(array $filters = [])
    {
        $this->filters = $this->normalizeFilters($filters);
    }

    private function normalizeFilters(array $filters): array
    {
        return [
            'texto' => isset($filters['texto']) ? trim((string) $filters['texto']) : null,
            'equipo_id' => (array) ($filters['equipo_id'] ?? []),
            'recurso_id' => (array) ($filters['recurso_id'] ?? []),
            'destino_id' => (array) ($filters['destino_id'] ?? []),
            'estado_id' => (array) ($filters['estado_id'] ?? []),
            'destino_actual_id' => (array) ($filters['destino_actual_id'] ?? []),
            'tipo_terminal_id' => (array) ($filters['tipo_terminal_id'] ?? []),
            'fecha_rango' => isset($filters['fecha_rango']) ? trim((string) $filters['fecha_rango']) : null,
            'ticket_per' => isset($filters['ticket_per']) ? trim((string) $filters['ticket_per']) : null,
            'observaciones' => isset($filters['observaciones']) ? trim((string) $filters['observaciones']) : null,
        ];
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $query = Equipo::select(
            'equipos.id', // A (ID original, será reemplazado en el mapeo)
            DB::raw("CONCAT(tipo_terminales.marca, ' ', tipo_terminales.modelo) AS terminal"), // B
            'estados.nombre as estado', // C
            'equipos.provisto as provisto', // D
            'destino.nombre as dependencia', // E
            'equipos.nombre_issi as id_issi', // F
            'equipos.issi as issi', // G
            'equipos.tei as tei', // H
            'recursos.nombre as recurso', // I
            'destino_padre.nombre as dependencia_superior', // J
            // Datos del histórico
            'historico.fecha_asignacion', // K
            // Datos adicionales de FlotaGeneral
            'flota_general.ticket_per', // L
            // Datos del vehículo (si existe)
            'vehiculos.marca as vehiculo_marca', // M
            'vehiculos.modelo as vehiculo_modelo', // N
            'vehiculos.dominio as vehiculo_patente', // O
            'flota_general.observaciones as observaciones_flota' // P
        )
            ->leftJoin('flota_general', 'equipos.id', '=', 'flota_general.equipo_id')
            ->leftJoin('recursos', 'flota_general.recurso_id', '=', 'recursos.id')
            ->leftJoin('destino', 'flota_general.destino_id', '=', 'destino.id')
            ->leftJoin('destino as destino_padre', 'destino.parent_id', '=', 'destino_padre.id')
            ->leftJoin('vehiculos', 'recursos.vehiculo_id', '=', 'vehiculos.id')
            ->leftJoin('tipo_terminales', 'equipos.tipo_terminal_id', '=', 'tipo_terminales.id')
            ->leftJoin('estados', 'equipos.estado_id', '=', 'estados.id')
            // Subquery para obtener solo la fecha de asignación más reciente por equipo
            ->leftJoin(DB::raw('(
                SELECT
                    equipo_id,
                    MAX(fecha_asignacion) as fecha_asignacion
                FROM historico
                WHERE fecha_desasignacion IS NULL
                AND fecha_asignacion IS NOT NULL
                GROUP BY equipo_id
            ) as historico'), 'equipos.id', '=', 'historico.equipo_id')
            // Agrupar por equipo.id para evitar duplicados
            ->groupBy(
                'equipos.id',
                'tipo_terminales.marca',
                'tipo_terminales.modelo',
                'estados.nombre',
                'equipos.tei',
                'equipos.issi',
                'equipos.nombre_issi',
                'equipos.provisto',
                'recursos.nombre',
                'destino.nombre',
                'destino_padre.nombre',
                'historico.fecha_asignacion',
                'flota_general.ticket_per',
                'vehiculos.marca',
                'vehiculos.modelo',
                'vehiculos.dominio',
                'flota_general.observaciones'
            )

            ;

        $query = $this->applyFilters($query);

        return $query->get();
    }

    private function applyFilters($query)
    {
        $filters = $this->filters;

        if (!empty($filters['texto'])) {
            $texto = $filters['texto'];
            $query->where(function ($q) use ($texto) {
                $q->where('equipos.issi', 'like', '%' . $texto . '%')
                    ->orWhere('equipos.tei', 'like', '%' . $texto . '%')
                    ->orWhere('recursos.nombre', 'like', '%' . $texto . '%')
                    ->orWhere('destino.nombre', 'like', '%' . $texto . '%');
            });
        }

        if (!empty($filters['equipo_id'])) {
            $query->whereIn('equipos.id', $filters['equipo_id']);
        }

        if (!empty($filters['recurso_id'])) {
            $query->whereIn('flota_general.recurso_id', $filters['recurso_id']);
        }

        if (!empty($filters['destino_actual_id'])) {
            $todosLosDestinosActuales = collect();

            foreach ($filters['destino_actual_id'] as $destinoId) {
                $destinosHijos = Destino::obtenerTodosLosHijos($destinoId);
                $todosLosDestinosActuales = $todosLosDestinosActuales->merge($destinosHijos);
            }

            $destinosActualesIds = $todosLosDestinosActuales->unique()->values()->all();

            if (!empty($destinosActualesIds)) {
                $query->whereExists(function ($subQuery) use ($destinosActualesIds) {
                    $subQuery->select(DB::raw(1))
                        ->from('historico as historico_actual')
                        ->whereColumn('historico_actual.equipo_id', 'equipos.id')
                        ->whereIn('historico_actual.destino_id', $destinosActualesIds)
                        ->whereNull('historico_actual.fecha_desasignacion');
                });
            }
        }

        if (!empty($filters['destino_id'])) {
            $todosLosDestinos = collect();

            foreach ($filters['destino_id'] as $destinoId) {
                $destinosHijos = Destino::obtenerTodosLosHijos($destinoId);
                $todosLosDestinos = $todosLosDestinos->merge($destinosHijos);
            }

            $destinosIds = $todosLosDestinos->unique()->values()->all();

            if (!empty($destinosIds)) {
                $query->whereIn('flota_general.destino_id', $destinosIds);
            }
        }

        if (!empty($filters['tipo_terminal_id'])) {
            $query->whereIn('equipos.tipo_terminal_id', $filters['tipo_terminal_id']);
        }

        if (!empty($filters['estado_id'])) {
            $query->whereIn('equipos.estado_id', $filters['estado_id']);
        }

        if (!empty($filters['fecha_rango'])) {
            $partes = explode(' - ', $filters['fecha_rango']);

            if (count($partes) === 2) {
                $fechaInicio = Carbon::parse($partes[0])->startOfDay();
                $fechaFin = Carbon::parse($partes[1])->endOfDay();

                $query->whereExists(function ($subQuery) use ($fechaInicio, $fechaFin) {
                    $subQuery->select(DB::raw(1))
                        ->from('historico as historico_fecha')
                        ->whereColumn('historico_fecha.equipo_id', 'equipos.id')
                        ->whereBetween('historico_fecha.fecha_asignacion', [$fechaInicio, $fechaFin])
                        ->whereNull('historico_fecha.fecha_desasignacion');
                });
            }
        }

        if (!empty($filters['ticket_per'])) {
            $query->where('flota_general.ticket_per', 'like', '%' . $filters['ticket_per'] . '%');
        }

        if (!empty($filters['observaciones'])) {
            $query->where('flota_general.observaciones', 'like', '%' . $filters['observaciones'] . '%');
        }

        return $query;
    }

    /**
     * Mapea cada fila reemplazando el ID por un número incremental
     */
    public function map($equipo): array
    {
        $this->rowNumber++;

        return [
            $this->rowNumber, // A Número incremental en lugar del ID
            $equipo->terminal, // B
            $equipo->estado, // C
            $equipo->provisto, // D
            $equipo->dependencia, // E
            $equipo->id_issi, // F
            $equipo->issi, // G
            $equipo->tei, // H
            $equipo->recurso, // I
            $equipo->dependencia_superior, // J
            $equipo->fecha_asignacion, // K
            $equipo->ticket_per, // L
            $equipo->vehiculo_marca, // M
            $equipo->vehiculo_modelo, // N
            $equipo->vehiculo_patente, // O
            $equipo->observaciones_flota // P
        ];
    }

    public function headings(): array
    {
        return [
            'Nro', //A
            'Terminal', //B
            'Estado', //C
            'Provisto', //D
            'Dependencia', //E
            'ID ISSI', //F
            'ISSI', //G
            'TEI', //H
            'Recurso', //I
            'Dependencia Superior', //J
            'Fecha Asignación', //K
            'Ticket PER', //L
            'Vehículo Marca', //M
            'Vehículo Modelo', //N
            'Dominio', //O
            'Observaciones' //P
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                // Obtener la última columna con datos
                $lastColumn = $event->sheet->getHighestColumn();
                $headerRange = 'A1:' . $lastColumn . '1';

                // Tamaño letra de cabecera
                $event->sheet->getStyle($headerRange)->getFont()->setSize(14);

                // Centrar solo las cabeceras
                $event->sheet->getStyle($headerRange)->getAlignment()->setHorizontal(
                    \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
                );

                // Cabeceras en negrita
                $event->sheet->getStyle($headerRange)->getFont()->setBold(true);

                // Filtros en cabecera
                $event->sheet->setAutoFilter($headerRange);

                // 🔹 Establecer la columna H como texto
                $lastRow = $event->sheet->getHighestRow();
                $event->sheet->getStyle('H2:H' . $lastRow)
                    ->getNumberFormat()
                    ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);

                // 🔹 Centrar contenido solo de la columna A a la O
                $event->sheet->getStyle('A:O')
                    ->getAlignment()
                    ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

                $event->sheet->getStyle('P1')
                    ->getAlignment()
                    ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);

                // 🔹 Inmovilizar cabecera
                $event->sheet->freezePane('A2');
            },
        ];
    }
}
