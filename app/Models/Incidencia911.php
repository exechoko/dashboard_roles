<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Incidencia911 extends Model
{
    protected $table = 'incidencias_911';

    protected $fillable = [
        'periodo_id', 'tipo_incidencia', 'hoja_origen',
        'incidencia_code', 'tickets',
        'fecha_inicio_falla',
        'minutos_fallo', 'minutos_exc',
        'n_unidades_afectadas', 'n_total_unidades',
        'sistema', 'modulo_n2', 'modulo_n3', 'subsistema_raw',
        'ponderacion_n2', 'ponderacion_n1',
        'prioridad', 'aplica_calculo', 'estado',
        'equipo_modelo', 'periodo_facturado',
        'observaciones',
    ];

    protected $casts = [
        'fecha_inicio_falla' => 'datetime',
        'aplica_calculo'     => 'boolean',
        'minutos_fallo'      => 'float',
        'minutos_exc'        => 'float',
        'ponderacion_n2'     => 'float',
        'ponderacion_n1'     => 'float',
    ];

    /**
     * Mapa de columnas para importar desde planilla de incidencias.
     * Índices basados en 1 (tal como los devuelve Excel COM o PhpSpreadsheet).
     *
     * Formato P01: hoja "Redengas - P.G." (27 columnas)
     * Formato P49: hoja "Reclamos"        (32 columnas)
     */
    /**
     * Mapa de columnas para importar desde planilla de incidencias.
     * Índices basados en 1 (tal como los devuelve Excel COM o PhpSpreadsheet).
     * El importador convierte a 0-indexed restando 1.
     *
     * Formato P01: hoja "Patagonia" / "Redengas - P.G." (27 columnas)
     * Formato P49: hoja "Reclamos"                      (32 columnas)
     */
    const COLUMNAS_PLANILLA = [
        'P01' => [
            'incidencia_code'  => 1,    // Incid.
            'tickets'          => 2,    // Ticket (Nro.)
            'fecha_inicio_falla' => 5,  // Fecha Inicio Incidencia
            'fecha_fin_falla'  => 12,   // F. FIN falla.
            'fecha_sol'        => 18,   // F. Solución
            'estado'           => 10,   // Estado
            'prioridad_ticket' => 9,    // Priorid. Ticket
            'descripcion'      => 11,   // Incidencia (texto largo)
            'periodo_facturado'=> 14,   // Periodo Facturado
            'aplica_calculo'   => 26,   // Aplica multa
            'subsistema_raw'   => 21,   // Subsist. Donde se produjo inc.
            'prioridad'        => 15,   // Prioridad Pliego
            'minutos_fallo'    => 19,   // Min. que Falló
            'minutos_exc'      => 20,   // Minutos Exc.
            'pct_falla_items'  => 22,   // % falla items  (n_afect/n_total × 100)
            'n_total_unidades' => 23,   // Cantidad de Items
            'equipo_modelo'    => null, // No existe en P01
        ],
        'P49' => [
            'incidencia_code'  => 1,    // Inc.
            'tickets'          => 2,    // Ticket (Nro.)
            'fecha_inicio_falla' => 5,  // Fecha Inicio Inc./Prest.
            'fecha_fin_falla'  => 15,   // F. FIN falla.
            'fecha_sol'        => 7,    // F. Solución
            'estado'           => 13,   // Estado
            'prioridad_ticket' => 10,   // Prior. Ticket
            'descripcion'      => 14,   // Incidencia / Prestación
            'aplica_calculo'   => 11,   // Apl.
            'periodo_facturado'=> 12,   // Per. Fact.
            'subsistema_raw'   => 17,   // Subsist. Donde se produjo inc.
            'prioridad'        => 18,   // Prioridad Pliego
            'minutos_fallo'    => 20,   // Min. que Falló
            'minutos_exc'      => 21,   // Min. Exc.
            'pct_falla_items'  => 24,   // % falla items
            'n_total_unidades' => 25,   // Cant. Items
            'equipo_modelo'    => 30,   // Equipo (Modelo)
        ],
    ];

    const PRIORIDADES = [
        'critico' => 'Crítico',
        'alto'    => 'Alto',
        'medio'   => 'Medio',
        'bajo'    => 'Bajo',
    ];

    const TIPOS = [
        'persistente' => 'Persistente',
        'transitoria' => 'Transitoria',
        'manual'      => 'Manual',
    ];

    const HOJAS_ORIGEN = [
        'preventivos' => 'Preventivos',
        'patagonia'   => 'Patagonia',
        'telecom'     => 'Telecom',
        'ute'         => 'U.T.E.',
        'manual'      => 'Manual',
        'arrastrado'  => 'Arrastrado (período anterior)',
    ];

    /**
     * Estructura de módulos del pliego (Anexo V).
     * n1_peso: ponderación del sistema de primer nivel (%)
     * modulos_n2: nombre => peso N2 (%)
     */
    const MODULOS = [
        'CCTV' => [
            'n1_peso'   => 25,
            'modulos_n2' => [
                'Módulo Monitoreo'         => 40,
                'Módulo Grabación'         => 50,
                'Módulo Admin y Extracción'=> 10,
                'Cámaras'                  => 100,
                'Total'                    => 100,
                'Latente'                  => 5,
            ],
        ],
        'TETRA' => [
            'n1_peso'   => 20,
            'modulos_n2' => [
                'Módulo Grabación'         => 10,
                'Módulo Admin y Extracción'=> 10,
                'Comunicación'             => 100,
                'Total'                    => 100,
                'Latente'                  => 5,
            ],
        ],
        'Emergencias 911' => [
            'n1_peso'   => 20,
            'modulos_n2' => [
                'Módulo Servicios'                   => 25,
                'Módulo Comunicación de emergencia'  => 25,
                'Módulo GIS'                         => 20,
                'Módulo CCTV'                        => 20,
                'Módulo Admin y Extracción'          => 10,
                'Puestos de Emergencias'             => 100,
                'Total'                              => 100,
                'Latente'                            => 5,
            ],
        ],
        'Infraestructura' => [
            'n1_peso'   => 20,
            'modulos_n2' => [
                'Energía General Generadores' => 25,
                'Energía Segurizada UPS'      => 15,
                'Red F.O. externo'            => 30,
                'Cableado interno'            => 30,
                'Sistema de Telefonía'        => 20,
                'Terminales de Telefonía'     => 20,
                'Climatización técnica'       => 10,
                'Total'                       => 100,
                'Latente'                     => 5,
            ],
        ],
        'Prestación de Servicio' => [
            'n1_peso'   => 15,
            'modulos_n2' => [
                'Tareas de Reclamos y ajustes' => 30,
                'Soporte'                      => 25,
                'Sist. Admin de reclamos'      => 25,
                'Capacitación'                 => 20,
                'Total'                        => 100,
                'Latente'                      => 5,
            ],
        ],
    ];

    // ── Relaciones ────────────────────────────────────────────────────────────

    public function periodo()
    {
        return $this->belongsTo(PeriodoFactura::class, 'periodo_id');
    }

    // ── Cálculos ──────────────────────────────────────────────────────────────

    /** % indisponibilidad de esta incidencia respecto al período */
    public function pctIndisponibilidad(): float
    {
        $T = $this->periodo?->minutos_totales ?? 0;
        if ($T <= 0 || $this->n_total_unidades <= 0 || !$this->aplica_calculo) {
            return 0.0;
        }
        // (% ítems afectados) × (% tiempo que falló) = % tiempo falla calc del ítem
        return ($this->n_unidades_afectadas / $this->n_total_unidades)
            * ($this->minutos_fallo / $T)
            * 100;
    }

    /**
     * % deficiencia funcional del ítem.
     * El pliego considera 50 % de falla como 100 % de deficiencia, por eso × 2.
     */
    public function pctDeficiencia(): float
    {
        return $this->pctIndisponibilidad() * 2;
    }

    /** Contribución al módulo N2 (deficiencia ponderada por el peso N3 = 1/n_total) */
    public function contribN2(): float
    {
        if ($this->n_total_unidades <= 0) {
            return 0.0;
        }
        return $this->pctDeficiencia() / $this->n_total_unidades;
    }

    /** Contribución al sistema N1 (contribN2 × peso N2) */
    public function contribN1(): float
    {
        return $this->contribN2() * ($this->ponderacion_n2 / 100);
    }

    /** Contribución al % TOTAL ponderado (contribN1 × peso N1) */
    public function contribTotal(): float
    {
        return $this->contribN1() * ($this->ponderacion_n1 / 100);
    }

    /** Minutos de excedente sobre SLA — base para cálculo de multa */
    public function minutosExcSla(): float
    {
        return max(0.0, (float) $this->minutos_exc);
    }

    // ── Import helpers ────────────────────────────────────────────────────────

    /**
     * Parsea el campo "Subsist. Donde se produjo inc." de la planilla y devuelve
     * los tres niveles normalizados.
     *
     * Ejemplo: "Sist. CCTV - Módulo Monitoreo - Por cámara"
     *   → ['sistema' => 'CCTV', 'modulo_n2' => 'Módulo Monitoreo', 'modulo_n3' => 'Por cámara']
     *
     * Funciona igual para P01 y P49 porque ambas planillas usan el mismo formato.
     */
    public static function parsearSubsistema(string $raw): array
    {
        $parts = array_map('trim', explode(' - ', $raw, 3));

        // Quitar prefijo "Sist. " o "Emerg. " del primer nivel
        $n1 = preg_replace('/^(Sist\.|Emerg\.)\s*/i', '', $parts[0] ?? '');

        // Normalizar abreviaturas usadas en los archivos Excel de P01–P49
        $n1Lower = strtolower($n1);
        if (str_starts_with($n1Lower, 'prest')) {
            $n1 = 'Prestación de Servicio';
        } elseif (str_starts_with($n1Lower, 'infraest')) {
            $n1 = 'Infraestructura';
        } elseif ($n1 === '911' || $n1Lower === 'emergencias 911') {
            $n1 = 'Emergencias 911';
        }

        // Normalizar módulos N2 abreviados frecuentes
        $n2map = [
            'mód com. emergencias'           => 'Módulo Comunicación de emergencia',
            'módulo comunicación de emergencias' => 'Módulo Comunicación de emergencia',
            'mód. monitoreo'                 => 'Módulo Monitoreo',
            'modulo monitoreo'               => 'Módulo Monitoreo',
            'mód. grabación'                 => 'Módulo Grabación',
            'modulo grabacion'               => 'Módulo Grabación',
            'mód. admin y extracción'        => 'Módulo Admin y Extracción',
            'modulo cctv'                    => 'Módulo CCTV',
            'modulo gis'                     => 'Módulo GIS',
            'mod administ extracción'        => 'Módulo Admin y Extracción',
            'cámaras'                        => 'Cámaras',
            'camaras'                        => 'Cámaras',
            'puestos emergencias 911'        => 'Puestos de Emergencias',
            'climatización técnica'          => 'Climatización técnica',
            'sist. admin de reclamos'        => 'Sist. Admin de reclamos',
            'sist administración reclamos'   => 'Sist. Admin de reclamos',
        ];
        $n2key = strtolower($parts[1] ?? '');
        $n2    = $n2map[$n2key] ?? ($parts[1] ?? '');

        return [
            'sistema'   => $n1,
            'modulo_n2' => $n2,
            'modulo_n3' => $parts[2] ?? '',
        ];
    }

    /**
     * Construye un array de atributos listos para create()/fill() a partir de
     * una fila de la planilla de incidencias.
     *
     * @param array  $row     Fila como array indexado desde 1 (compatible con
     *                        PhpSpreadsheet::toArray con headingRow=false)
     * @param string $formato 'P01' o 'P49'
     */
    public static function desdeFilaPlanilla(array $row, string $formato = 'P49'): array
    {
        $cols = self::COLUMNAS_PLANILLA[$formato] ?? self::COLUMNAS_PLANILLA['P49'];
        $get  = fn(string $key) => trim((string) ($row[$cols[$key]] ?? ''));

        $subsRaw    = $get('subsistema_raw');
        $niveles    = $subsRaw ? self::parsearSubsistema($subsRaw) : [];
        $ponderacion = isset($niveles['sistema'], $niveles['modulo_n2'])
            ? self::ponderacionPara($niveles['sistema'], $niveles['modulo_n2'])
            : ['n1' => 0, 'n2' => 0];

        $pctFalla       = (float) str_replace(',', '.', $get('pct_falla_items'));
        $nTotal         = (int)   str_replace(',', '.', $get('n_total_unidades'));
        $nAfectadas     = $nTotal > 0 ? (int) round($nTotal * $pctFalla / 100) : 1;

        $prioridad = self::normalizarPrioridad($get('prioridad'));
        $aplica    = self::normalizarAplica($get('aplica_calculo'));

        return [
            'incidencia_code'   => $get('incidencia_code')   ?: null,
            'tickets'           => $get('tickets')           ?: null,
            'fecha_inicio_falla'=> $get('fecha_inicio_falla') ?: null,
            'minutos_fallo'     => (float) str_replace(',', '.', $get('minutos_fallo')),
            'minutos_exc'       => (float) str_replace(',', '.', $get('minutos_exc')),
            'n_unidades_afectadas' => max(1, $nAfectadas),
            'n_total_unidades'  => max(1, $nTotal),
            'sistema'           => $niveles['sistema']   ?? '',
            'modulo_n2'         => $niveles['modulo_n2'] ?? '',
            'modulo_n3'         => $niveles['modulo_n3'] ?? '',
            'subsistema_raw'    => $subsRaw              ?: null,
            'ponderacion_n2'    => $ponderacion['n2'],
            'ponderacion_n1'    => $ponderacion['n1'],
            'prioridad'         => $prioridad,
            'aplica_calculo'    => $aplica,
            'equipo_modelo'     => $get('equipo_modelo') ?: null,
            'periodo_facturado' => $get('periodo_facturado') ?: null,
        ];
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function getColorPrioridadAttribute(): string
    {
        return match ($this->prioridad) {
            'critico' => 'danger',
            'alto'    => 'warning',
            'medio'   => 'info',
            default   => 'secondary',
        };
    }

    /** Devuelve peso N1 y N2 para un sistema/módulo dados */
    public static function ponderacionPara(string $sistema, string $moduloN2): array
    {
        $mod = self::MODULOS[$sistema] ?? null;
        if (!$mod) {
            return ['n1' => 0, 'n2' => 0];
        }
        $n2 = $mod['modulos_n2'][$moduloN2] ?? 0;
        return ['n1' => $mod['n1_peso'], 'n2' => $n2];
    }

    private static function normalizarPrioridad(string $raw): string
    {
        return match (strtolower(trim($raw))) {
            'bloqueante', 'crítico', 'critico' => 'critico',
            'grave', 'alto'                    => 'alto',
            'menor', 'medio'                   => 'medio',
            default                            => 'bajo',
        };
    }

    private static function normalizarAplica(string $raw): bool
    {
        $v = strtoupper(trim($raw));
        return in_array($v, ['SI', 'SÍ', 'YES', '1', 'TRUE', 'SI (PERM.)'], true);
    }
}
