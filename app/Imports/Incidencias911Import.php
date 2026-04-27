<?php

namespace App\Imports;

use App\Models\Incidencia911;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class Incidencias911Import implements WithMultipleSheets, ToModel, WithStartRow, WithCalculatedFormulas
{
    protected int     $periodoId;
    protected int     $periodoNumero;
    protected string  $hoja;
    protected int     $nTotalTetra;
    protected int     $nTotalCamaras;
    protected ?Carbon $fechaFinPeriodo;
    protected string  $nombreHojaExcel;

    public int $importados   = 0;
    public int $omitidos     = 0;
    public int $persistentes = 0;
    public int $transitorias = 0;

    protected int $minutosPeriodo = 0;

    private array $hojaMap = [
        'patagonia'            => 'Patagonia',
        'preventivos'          => 'Preventivos',
        'telecom'              => 'Telecom',
        'ute'                  => 'U.T.E.',
        'persistentes_tabla'   => 'Persistentes',
    ];

    public function __construct(
        int $periodoId, int $periodoNumero,
        string $hoja = 'patagonia',
        int $nTotalTetra = 0, int $nTotalCamaras = 0,
        ?string $fechaFinPeriodo = null,
        string $nombreHojaExcel = '',
        int $minutosPeriodo = 0
    ) {
        $this->periodoId       = $periodoId;
        $this->periodoNumero   = $periodoNumero;
        $this->hoja            = $hoja;
        $this->nTotalTetra     = $nTotalTetra;
        $this->nTotalCamaras   = $nTotalCamaras;
        $this->fechaFinPeriodo = $fechaFinPeriodo ? Carbon::parse($fechaFinPeriodo)->endOfDay() : null;
        $this->nombreHojaExcel = $nombreHojaExcel ?: ($this->hojaMap[$hoja] ?? 'Patagonia');
        $this->minutosPeriodo  = $minutosPeriodo;
    }

    public function sheets(): array
    {
        return [$this->nombreHojaExcel => $this];
    }

    /**
     * Hoja Patagonia (desde fila 3):
     * 0=Inc, 1=Ticket, 2=ID Ref, 3=Fecha Web, 4=F.Inicio, 5=F.Resp, 6=F.Sol,
     * 7=Gen, 8=Categ, 9=Prior.Ticket, 10=Apl, 11=Per.Fact, 12=Estado,
     * 13=Descripción, 14=F.FIN, 15=Resp.PG, 16=Subsist, 17=Prior.Pliego,
     * 18=Min.Sol, 19=Min.Falló
     *
     * Hoja Persistentes (desde fila 2, Tabla Ponderación):
     * 0=Inc, 1=Ticket, 2=ID Ref, 3=Fecha Web, 4=F.Inicio, 5=F.Resp, 6=F.Sol,
     * 7=Aplica Multa, 8=Gen, 9=Categoría, 10=Prior.Ticket, 11=Estado,
     * 12=Descripción, 13=F.FIN, 14=Respuestas
     */
    public function model(array $row): ?Incidencia911
    {
        if ($this->hoja === 'persistentes_tabla') {
            return $this->modelFromPersistentes($row);
        }

        $incCode = trim((string)($row[0] ?? ''));
        if (empty($incCode) || strtolower($incCode) === 'inc.' || strtolower($incCode) === 'inc') {
            $this->omitidos++;
            return null;
        }

        // Filtrar por período (Per. Fact. = P01, P02..., P49...)
        $perFact     = strtoupper(trim((string)($row[11] ?? '')));
        $perEsperado = 'P' . str_pad($this->periodoNumero, 2, '0', STR_PAD_LEFT);
        if ($perFact !== $perEsperado && $perFact !== 'P' . $this->periodoNumero) {
            $this->omitidos++;
            return null;
        }

        // Ticket de la ticketera PG (puede haber 2 tickets para la misma incidencia)
        $ticket = trim((string)($row[1] ?? ''));

        // Si la incidencia ya fue importada en esta misma pasada, solo agregar el ticket adicional
        $existente = \App\Models\Incidencia911::where('periodo_id', $this->periodoId)
            ->where('incidencia_code', $incCode)
            ->first();

        if ($existente) {
            if ($ticket && !str_contains((string)$existente->tickets, $ticket)) {
                $existente->tickets = $existente->tickets
                    ? $existente->tickets . ' / ' . $ticket
                    : $ticket;
                $existente->save();
            }
            $this->omitidos++;
            return null;
        }

        // Incumbencia (subsistema afectado)
        $incumbencia = trim((string)($row[16] ?? ''));

        // Deducir sistema, módulo N2 y N total
        [$sistema, $moduloN2, $nTotal] = $this->deducirSistema($incumbencia);

        // Ponderación
        $pond = Incidencia911::ponderacionPara($sistema, $moduloN2);

        // Fecha inicio de falla
        $fechaInicioStr = $this->parsearFecha($row[4] ?? null);

        // Auto-detectar tipo según F.Sol (col6) y F.FIN (col14)
        $fechaSol           = $this->parsearFecha($row[6] ?? null);
        $fechaFinIncidencia = $this->parsearFecha($row[14] ?? null);
        $tieneResolucion    = $fechaSol !== null || $fechaFinIncidencia !== null;
        $tipoIncidencia     = $tieneResolucion ? 'transitoria' : 'persistente';

        // Minutos fallados:
        //  - Transitorias (con F.Sol o F.FIN): usar col 19 (Min. Falló) del Excel.
        //  - Persistentes (sin resolución al cierre): el fallo cubre TODO el período.
        //    Se ignora col 19 porque puede traer minutos acumulados de periodos previos
        //    o valores parciales que no reflejan el tiempo dentro de ESTE período.
        if ($tipoIncidencia === 'persistente') {
            $minutosFallo = (float) ($this->minutosPeriodo ?: 40320);
        } else {
            $minutosFallo = $this->parsearNumero($row[19] ?? 0);
            if ($minutosFallo <= 0 && $this->fechaFinPeriodo !== null && $fechaInicioStr !== null) {
                $minutosFallo = (float) Carbon::parse($fechaInicioStr)->diffInMinutes($this->fechaFinPeriodo);
            }
        }

        // Prioridad (Prior. Ticket: Bajo/Medio/Alto/Crítico)
        $prioridadRaw = strtolower(trim((string)($row[9] ?? 'medio')));
        $prioridad = match (true) {
            str_contains($prioridadRaw, 'crit') => 'critico',
            str_contains($prioridadRaw, 'alto') => 'alto',
            str_contains($prioridadRaw, 'bajo') => 'bajo',
            default                             => 'medio',
        };

        $estado = strtolower(trim((string)($row[12] ?? 'resuelto')));

        // Aplica al cálculo de multa (columna "Apl." = row[10])
        // El Excel usa "Si"/"No", "X"/"", TRUE/FALSE, 1/0, etc.
        $aplicaRaw   = strtolower(trim((string)($row[10] ?? 'si')));
        $aplica      = !in_array($aplicaRaw, ['no', 'false', '0', '']);

        if ($tipoIncidencia === 'persistente') {
            $this->persistentes++;
        } else {
            $this->transitorias++;
        }
        $this->importados++;

        return new Incidencia911([
            'periodo_id'           => $this->periodoId,
            'tipo_incidencia'      => $tipoIncidencia,
            'hoja_origen'          => $this->hoja,
            'incidencia_code'      => $incCode,
            'tickets'              => $ticket,
            'fecha_inicio_falla'   => $fechaInicioStr,
            'minutos_fallo'        => $minutosFallo,
            'n_unidades_afectadas' => 1,
            'n_total_unidades'     => $nTotal ?: 1,
            'sistema'              => $sistema,
            'modulo_n2'            => $moduloN2,
            'ponderacion_n2'       => $pond['n2'],
            'ponderacion_n1'       => $pond['n1'],
            'prioridad'            => $prioridad,
            'aplica_calculo'       => $aplica,
            'estado'               => $estado,
            'observaciones'        => mb_substr(trim((string)($row[13] ?? '')), 0, 500),
        ]);
    }

    public function startRow(): int
    {
        // Hoja Persistentes comienza en fila 2 (fila 1 = encabezado)
        return $this->hoja === 'persistentes_tabla' ? 2 : 3;
    }

    /**
     * Importa desde la hoja "Persistentes" del archivo Tabla Ponderación.
     * Solo importa incidencias sin fecha de resolución (aún activas).
     * Los minutos se asignan como el total del período (falla persistente = todo el período).
     *
     * Columnas (0-indexed): 0=Inc, 1=Ticket, 2=IDRef, 3=FechaWeb, 4=FInicio,
     * 5=FRespuesta, 6=FSol, 7=AplicaMulta, 8=Gen, 9=Categoría,
     * 10=Prior, 11=Estado, 12=Descripción, 13=FFIN, 14=Respuestas
     */
    private function modelFromPersistentes(array $row): ?Incidencia911
    {
        $incCode = trim((string)($row[0] ?? ''));
        if (empty($incCode) || str_starts_with(strtolower($incCode), 'incid')) {
            $this->omitidos++;
            return null;
        }

        // Estado "Resuelto" → omitir
        $estado = strtolower(trim((string)($row[11] ?? '')));
        if ($estado === 'resuelto') {
            $this->omitidos++;
            return null;
        }

        // F.Sol (col 6) con fecha válida → resuelto → omitir
        $fechaSol = $this->parsearFecha($row[6] ?? null);
        $fechaFin = $this->parsearFecha($row[13] ?? null);
        if ($fechaSol !== null || $fechaFin !== null) {
            $this->omitidos++;
            return null;
        }

        // Si ya existe en este período, omitir
        $existente = Incidencia911::where('periodo_id', $this->periodoId)
            ->where('incidencia_code', $incCode)
            ->first();
        if ($existente) {
            $this->omitidos++;
            return null;
        }

        // Deducir sistema desde la Categoría (col 9)
        $categoria = trim((string)($row[9] ?? ''));
        [$sistema, $moduloN2, $nTotal] = $this->deducirSistema($categoria);

        $pond = Incidencia911::ponderacionPara($sistema, $moduloN2);

        $fechaInicioStr = $this->parsearFecha($row[4] ?? null);
        $ticket         = trim((string)($row[1] ?? ''));

        $aplicaRaw = strtolower(trim((string)($row[7] ?? 'si')));
        $aplica    = !in_array($aplicaRaw, ['no', 'false', '0', '']);

        $prioridadRaw = strtolower(trim((string)($row[10] ?? 'medio')));
        $prioridad = match (true) {
            str_contains($prioridadRaw, 'crit') => 'critico',
            str_contains($prioridadRaw, 'alto') => 'alto',
            str_contains($prioridadRaw, 'bajo') => 'bajo',
            default                             => 'medio',
        };

        $this->persistentes++;
        $this->importados++;

        return new Incidencia911([
            'periodo_id'           => $this->periodoId,
            'tipo_incidencia'      => 'persistente',
            'hoja_origen'          => 'arrastrado',
            'incidencia_code'      => $incCode,
            'tickets'              => $ticket,
            'fecha_inicio_falla'   => $fechaInicioStr,
            'minutos_fallo'        => (float) ($this->minutosPeriodo ?: 40320),
            'n_unidades_afectadas' => 1,
            'n_total_unidades'     => $nTotal ?: 1,
            'sistema'              => $sistema,
            'modulo_n2'            => $moduloN2,
            'ponderacion_n2'       => $pond['n2'],
            'ponderacion_n1'       => $pond['n1'],
            'prioridad'            => $prioridad,
            'aplica_calculo'       => $aplica,
            'estado'               => $estado ?: 'en_progreso',
            'observaciones'        => mb_substr(trim((string)($row[12] ?? '')), 0, 500),
        ]);
    }

    private function deducirSistema(string $incumbencia): array
    {
        $lower = strtolower($incumbencia);
        if (str_contains($lower, 'tetra')) {
            $sistema = 'TETRA';
            $nTotal  = $this->nTotalTetra ?: 622;
            $modulo  = str_contains($lower, 'comunicaci') ? 'Comunicación'
                : (str_contains($lower, 'grabaci') ? 'Módulo Grabación'
                : (str_contains($lower, 'admin') ? 'Módulo Admin y Extracción' : 'Comunicación'));
        } elseif (str_contains($lower, 'cctv') || str_contains($lower, 'cámara') || str_contains($lower, 'camara')) {
            $sistema = 'CCTV';
            $nTotal  = $this->nTotalCamaras ?: 336;
            $modulo  = str_contains($lower, 'monitoreo') ? 'Módulo Monitoreo'
                : (str_contains($lower, 'grabaci') ? 'Módulo Grabación'
                : (str_contains($lower, 'admin') ? 'Módulo Admin y Extracción' : 'Cámaras'));
        } elseif (str_contains($lower, '911') || str_contains($lower, 'emergencia')) {
            $sistema = 'Emergencias 911';
            $nTotal  = 1;
            $modulo  = 'Total';
        } elseif (str_contains($lower, 'infraestruc') || str_contains($lower, 'energía') || str_contains($lower, 'energia') || str_contains($lower, 'fibra')) {
            $sistema = 'Infraestructura';
            $nTotal  = 1;
            $modulo  = 'Total';
        } else {
            $sistema = 'Prestación de Servicio';
            $nTotal  = 1;
            $modulo  = 'Total';
        }

        return [$sistema, $modulo, $nTotal];
    }

    private function parsearFecha($value): ?string
    {
        if (empty($value)) {
            return null;
        }
        try {
            if (is_numeric($value)) {
                return Carbon::instance(ExcelDate::excelToDateTimeObject($value))->format('Y-m-d H:i:s');
            }
            $clean = trim((string)$value);
            foreach (['d/m/Y H:i', 'd/m/Y', 'Y-m-d H:i:s', 'Y-m-d'] as $fmt) {
                try {
                    return Carbon::createFromFormat($fmt, $clean)->format('Y-m-d H:i:s');
                } catch (\Exception $e) {
                    continue;
                }
            }
        } catch (\Exception $e) {
            // ignorar
        }
        return null;
    }

    private function parsearNumero($value): float
    {
        if (is_numeric($value)) {
            return (float)$value;
        }
        $clean = str_replace(['.', ','], ['', '.'], trim((string)$value));
        return is_numeric($clean) ? (float)$clean : 0.0;
    }
}
