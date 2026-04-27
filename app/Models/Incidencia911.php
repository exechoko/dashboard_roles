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
        'minutos_fallo',
        'n_unidades_afectadas', 'n_total_unidades',
        'sistema', 'modulo_n2',
        'ponderacion_n2', 'ponderacion_n1',
        'prioridad', 'aplica_calculo', 'estado', 'observaciones',
    ];

    protected $casts = [
        'fecha_inicio_falla' => 'datetime',
        'aplica_calculo'     => 'boolean',
        'minutos_fallo'      => 'float',
        'ponderacion_n2'     => 'float',
        'ponderacion_n1'     => 'float',
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

    /** % indisponibilidad de esta incidencia */
    public function pctIndisponibilidad(): float
    {
        $T = $this->periodo?->minutos_totales ?? 0;
        if ($T <= 0 || $this->n_total_unidades <= 0 || !$this->aplica_calculo) {
            return 0.0;
        }
        return ($this->n_unidades_afectadas / $this->n_total_unidades)
            * ($this->minutos_fallo / $T)
            * 100;
    }

    /** % deficiencia funcionamiento (= indisponibilidad * 2) */
    public function pctDeficiencia(): float
    {
        return $this->pctIndisponibilidad() * 2;
    }

    /** Contribución al módulo N1 (igual a la deficiencia — el peso N2 es solo informativo) */
    public function contribN1(): float
    {
        return $this->pctDeficiencia();
    }

    /** Contribución al total ponderado del sistema (contribN1 * peso N1) */
    public function contribTotal(): float
    {
        return $this->contribN1() * ($this->ponderacion_n1 / 100);
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
}
