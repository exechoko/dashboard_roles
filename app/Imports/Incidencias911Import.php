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
    protected int     $nTotalPuestosCctv;
    protected ?Carbon $fechaFinPeriodo;
    protected string  $nombreHojaExcel;

    public int $importados        = 0;
    public int $omitidos          = 0;
    public int $omitidosNoAplica  = 0;
    public int $persistentes      = 0;
    public int $transitorias      = 0;

    protected int $minutosPeriodo = 0;

    // 'P49' | 'P01' | null (auto-detect en primera fila)
    protected ?string $formato = null;

    // Mapa de columnas 0-indexed para el formato detectado
    private ?array $cols = null;

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
        int $nTotalTetra = 0, int $nTotalCamaras = 0, int $nTotalPuestosCctv = 0,
        ?string $fechaFinPeriodo = null,
        string $nombreHojaExcel = '',
        int $minutosPeriodo = 0,
        ?string $formato = null
    ) {
        $this->periodoId       = $periodoId;
        $this->periodoNumero   = $periodoNumero;
        $this->hoja            = $hoja;
        $this->nTotalTetra     = $nTotalTetra;
        $this->nTotalCamaras   = $nTotalCamaras;
        $this->nTotalPuestosCctv = $nTotalPuestosCctv;
        $this->fechaFinPeriodo = $fechaFinPeriodo ? Carbon::parse($fechaFinPeriodo)->endOfDay() : null;
        $this->nombreHojaExcel = $nombreHojaExcel ?: ($this->hojaMap[$hoja] ?? 'Patagonia');
        $this->minutosPeriodo  = $minutosPeriodo;
        $this->formato         = $formato; // null → auto-detectar en primera fila
    }

    public function sheets(): array
    {
        return [$this->nombreHojaExcel => $this];
    }

    public function startRow(): int
    {
        return $this->hoja === 'persistentes_tabla' ? 2 : 3;
    }

    // ── Método principal ──────────────────────────────────────────────────────

    public function model(array $row): ?Incidencia911
    {
        if ($this->hoja === 'persistentes_tabla') {
            return $this->modelFromPersistentes($row);
        }

        // Auto-detectar formato en la primera fila de datos
        if ($this->cols === null) {
            $this->resolverFormato($row);
        }

        $incCode = trim((string)($row[$this->c('incidencia_code')] ?? ''));
        if (empty($incCode) || strtolower($incCode) === 'inc.' || strtolower($incCode) === 'inc') {
            $this->omitidos++;
            return null;
        }

        // Filtrar por período
        $perFact = $this->normalizarPeriodo((string)($row[$this->c('periodo_facturado')] ?? ''));
        if ($perFact !== $this->periodoNumero) {
            $this->omitidos++;
            return null;
        }

        $ticket = trim((string)($row[$this->c('tickets')] ?? ''));

        // Evitar duplicados
        $existente = Incidencia911::where('periodo_id', $this->periodoId)
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

        // Aplica al cálculo
        $aplicaRaw = strtolower(trim((string)($row[$this->c('aplica_calculo')] ?? 'si')));
        $aplica    = !in_array($aplicaRaw, ['no', 'false', '0', '']);
        if (!$aplica) {
            $this->omitidos++;
            $this->omitidosNoAplica++;
            return null;
        }

        // Incumbencia → sistema, módulo N2, n total
        $incumbencia = trim((string)($row[$this->c('subsistema_raw')] ?? ''));

        // Intentar parsear el string completo para obtener sistema/módulo exactos
        $niveles = Incidencia911::parsearSubsistema($incumbencia);
        $pond    = Incidencia911::ponderacionPara($niveles['sistema'], $niveles['modulo_n2']);

        $moduloN3 = $niveles['modulo_n3'] ?? '';

        if ($pond['n2'] > 0) {
            $sistema  = $niveles['sistema'];
            $moduloN2 = $niveles['modulo_n2'];
            $nTotal   = $this->nTotalParaSistema($sistema, $moduloN3);
        } else {
            // Fallback a detección por palabras clave (subsistema no reconocido)
            [$sistema, $moduloN2, $nTotal] = $this->deducirSistema($incumbencia);
            $pond = Incidencia911::ponderacionPara($sistema, $moduloN2);
        }

        // Fechas
        $fechaInicioStr     = $this->parsearFecha($row[$this->c('fecha_inicio_falla')] ?? null);
        $fechaSol           = $this->parsearFecha($row[$this->c('fecha_sol')] ?? null);
        $fechaFinIncidencia = $this->parsearFecha($row[$this->c('fecha_fin_falla')] ?? null);
        $tieneResolucion    = $fechaSol !== null || $fechaFinIncidencia !== null;
        $tipoIncidencia     = $tieneResolucion ? 'transitoria' : 'persistente';

        // Minutos fallados
        if ($tipoIncidencia === 'persistente') {
            $minutosFallo = (float) ($this->minutosPeriodo ?: 40320);
        } else {
            $minutosFallo = $this->parsearNumero($row[$this->c('minutos_fallo')] ?? 0);
            if ($minutosFallo <= 0 && $this->fechaFinPeriodo !== null && $fechaInicioStr !== null) {
                $minutosFallo = (float) Carbon::parse($fechaInicioStr)->diffInMinutes($this->fechaFinPeriodo);
            }
        }

        // Prioridad
        $prioridadRaw = strtolower(trim((string)($row[$this->c('prioridad_ticket')] ?? 'medio')));
        $prioridad = match (true) {
            str_contains($prioridadRaw, 'crit') => 'critico',
            str_contains($prioridadRaw, 'alto') => 'alto',
            str_contains($prioridadRaw, 'bajo') => 'bajo',
            default                             => 'medio',
        };

        $estado = strtolower(trim((string)($row[$this->c('estado')] ?? 'resuelto')));

        // Minutos excedentes (solo en P49; en P01 también está disponible)
        $minutosExc = $this->parsearNumero($row[$this->c('minutos_exc')] ?? 0);

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
            'minutos_exc'          => $minutosExc,
            'n_unidades_afectadas' => 1,
            'n_total_unidades'     => $nTotal ?: 1,
            'sistema'              => $sistema,
            'modulo_n2'            => $moduloN2,
            'modulo_n3'            => $moduloN3,
            'subsistema_raw'       => mb_substr($incumbencia, 0, 250),
            'ponderacion_n2'       => $pond['n2'],
            'ponderacion_n1'       => $pond['n1'],
            'prioridad'            => $prioridad,
            'aplica_calculo'       => $aplica,
            'estado'               => $estado,
            'observaciones'        => mb_substr(trim((string)($row[$this->c('descripcion')] ?? '')), 0, 500),
        ]);
    }

    // ── Persistentes (hoja Tabla Ponderación) ─────────────────────────────────

    /**
     * Columnas (0-indexed):
     * 0=Inc, 1=Ticket, 2=IDRef, 3=FechaWeb, 4=FInicio, 5=FRespuesta, 6=FSol,
     * 7=AplicaMulta, 8=Gen, 9=Categoría, 10=Prior, 11=Estado, 12=Descripción,
     * 13=FFIN, 14=Respuestas
     */
    private function modelFromPersistentes(array $row): ?Incidencia911
    {
        $incCode = trim((string)($row[0] ?? ''));
        if (empty($incCode) || str_starts_with(strtolower($incCode), 'incid')) {
            $this->omitidos++;
            return null;
        }

        $estado = strtolower(trim((string)($row[11] ?? '')));
        if ($estado === 'resuelto') {
            $this->omitidos++;
            return null;
        }

        $fechaSol = $this->parsearFecha($row[6] ?? null);
        $fechaFin = $this->parsearFecha($row[13] ?? null);
        if ($fechaSol !== null || $fechaFin !== null) {
            $this->omitidos++;
            return null;
        }

        $existente = Incidencia911::where('periodo_id', $this->periodoId)
            ->where('incidencia_code', $incCode)
            ->first();
        if ($existente) {
            $this->omitidos++;
            return null;
        }

        $categoria = trim((string)($row[9] ?? ''));
        [$sistema, $moduloN2, $nTotal] = $this->deducirSistema($categoria);
        $pond = Incidencia911::ponderacionPara($sistema, $moduloN2);

        $fechaInicioStr = $this->parsearFecha($row[4] ?? null);
        $ticket         = trim((string)($row[1] ?? ''));
        $aplicaRaw      = strtolower(trim((string)($row[7] ?? 'si')));
        $aplica         = !in_array($aplicaRaw, ['no', 'false', '0', '']);

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

    // ── Detección de formato ──────────────────────────────────────────────────

    /**
     * Auto-detecta P01 o P49 mirando la primera fila de datos.
     *
     * Criterio: en P49, col 11 (0-indexed) = "Per. Fact." con valores como
     * "P01", "P49", etc. En P01, esa misma columna es "F. FIN falla." (fecha
     * o vacío). Si col 11 coincide con el patrón de código de período → P49.
     */
    private function resolverFormato(array $primeraFila): void
    {
        if ($this->formato !== null) {
            // Formato explícito pasado por el controlador
            $this->cols = $this->buildCols($this->formato);
            return;
        }

        $v = strtoupper(trim((string)($primeraFila[11] ?? '')));
        $this->formato = preg_match('/^P\d+$/', $v) ? 'P49' : 'P01';
        $this->cols    = $this->buildCols($this->formato);
    }

    /**
     * Convierte COLUMNAS_PLANILLA (1-indexed) a mapa 0-indexed para array access.
     */
    private function buildCols(string $formato): array
    {
        $map = Incidencia911::COLUMNAS_PLANILLA[$formato]
            ?? Incidencia911::COLUMNAS_PLANILLA['P49'];

        $result = [];
        foreach ($map as $campo => $col1indexed) {
            // null significa que el campo no existe en este formato → devolver índice fuera de rango
            $result[$campo] = $col1indexed !== null ? $col1indexed - 1 : 9999;
        }
        return $result;
    }

    /** Devuelve el índice 0-indexed para un campo del mapa de columnas. */
    private function c(string $campo): int
    {
        return $this->cols[$campo] ?? 9999;
    }

    // ── Helpers de negocio ────────────────────────────────────────────────────

    /**
     * Normaliza el valor de "Per. Fact." / "Periodo Facturado" a un número entero.
     * Acepta: "P01" → 1, "P49" → 49, "1" → 1, "Periodo 1" → 1, "01" → 1.
     */
    private function normalizarPeriodo(string $raw): int
    {
        $raw = strtoupper(trim($raw));
        if (preg_match('/(\d+)/', $raw, $m)) {
            return (int) $m[1];
        }
        return -1;
    }

    /** nTotal según sistema + N3: distingue "Por cámara" vs "Por puesto" para CCTV. */
    private function nTotalParaSistema(string $sistema, string $moduloN3 = ''): int
    {
        $n3 = strtolower(trim($moduloN3));

        if (in_array($n3, ['total', 'latente'], true)) {
            return 1;
        }

        return match ($sistema) {
            'TETRA'        => str_contains($n3, 'radio') ? 0 : ($this->nTotalTetra ?: 0),
            'CCTV'         => str_contains($n3, 'puesto') ? ($this->nTotalPuestosCctv ?: 0) : ($this->nTotalCamaras ?: 0),
            'Puestos CCTV' => $this->nTotalPuestosCctv ?: 0,
            default        => 1,
        };
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
        } elseif (str_contains($lower, 'cctv')) {
            if (str_contains($lower, 'puesto')) {
                $sistema = 'Puestos CCTV';
                $nTotal  = $this->nTotalPuestosCctv ?: 0;
                $modulo  = str_contains($lower, 'monitoreo') ? 'Módulo Monitoreo'
                    : (str_contains($lower, 'grabaci') ? 'Módulo Grabación'
                    : (str_contains($lower, 'admin') ? 'Módulo Admin y Extracción' : 'Puestos'));
            } elseif (str_contains($lower, 'cámara') || str_contains($lower, 'camara')) {
                $sistema = 'CCTV';
                $nTotal  = $this->nTotalCamaras ?: 336;
                $modulo  = str_contains($lower, 'monitoreo') ? 'Módulo Monitoreo'
                    : (str_contains($lower, 'grabaci') ? 'Módulo Grabación'
                    : (str_contains($lower, 'admin') ? 'Módulo Admin y Extracción' : 'Cámaras'));
            } else {
                $sistema = 'CCTV';
                $nTotal  = $this->nTotalCamaras ?: 336;
                $modulo  = 'Cámaras';
            }
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
