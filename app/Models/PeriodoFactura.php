<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class PeriodoFactura extends Model
{
    protected $table = 'periodos_factura';

    protected $fillable = [
        'numero', 'fecha_inicio', 'fecha_fin', 'dias', 'minutos_totales',
        'n_total_tetra', 'n_total_camaras', 'n_total_puestos_cecoco',
        'factura_numero', 'factura_monto', 'expediente_numero', 'ru_numero',
        'observaciones',
    ];

    protected $casts = [
        'fecha_inicio'   => 'date',
        'fecha_fin'      => 'date',
        'factura_monto'  => 'decimal:2',
    ];

    // Umbrales del Anexo V del pliego
    const UMBRAL_DEFICIENCIA_N1    = 2.0;   // % — por módulo de primer nivel
    const UMBRAL_DEFICIENCIA_TOTAL = 1.5;   // % — sistema completo ponderado

    // ── Relaciones ────────────────────────────────────────────────────────────

    public function incidencias()
    {
        return $this->hasMany(Incidencia911::class, 'periodo_id');
    }

    public function incidenciasAplica()
    {
        return $this->hasMany(Incidencia911::class, 'periodo_id')->where('aplica_calculo', true);
    }

    // ── Cálculos de indisponibilidad ─────────────────────────────────────────

    /**
     * Devuelve el análisis completo del período:
     * {
     *   'por_sistema' => [ 'TETRA' => ['deficiencia_n1' => ..., 'ponderacion_n1' => ..., 'contrib_total' => ...], ... ],
     *   'total_ponderado' => ...,
     *   'aplica_multa' => bool,
     *   'motivo_multa' => string|null,
     * }
     */
    public function analisis(): array
    {
        $incidencias = $this->incidenciasAplica()->get();
        $T = $this->minutos_totales;

        $porSistema = [];

        foreach ($incidencias as $inc) {
            if ($T <= 0 || $inc->n_total_unidades <= 0) {
                continue;
            }
            $indisponibilidad = ($inc->n_unidades_afectadas / $inc->n_total_unidades)
                * ($inc->minutos_fallo / $T)
                * 100;
            // Deficiencia = Indisp × 2, ponderada por el peso N2 del sub-módulo afectado.
            // Equivale a: E = Σ(L_n2 × M_n2) → C = E/0.5 del Anexo V.
            $deficiencia = $indisponibilidad * 2 * ($inc->ponderacion_n2 / 100);

            $sis = $inc->sistema ?: 'Sin sistema';
            if (!isset($porSistema[$sis])) {
                $porSistema[$sis] = [
                    'deficiencia_n1'   => 0.0,
                    'ponderacion_n1'   => (float) $inc->ponderacion_n1,
                    'contrib_total'    => 0.0,
                ];
            }
            $porSistema[$sis]['deficiencia_n1'] += $deficiencia;
        }

        $totalPonderado = 0.0;
        foreach ($porSistema as $sis => &$datos) {
            $datos['contrib_total'] = $datos['deficiencia_n1'] * ($datos['ponderacion_n1'] / 100);
            $totalPonderado += $datos['contrib_total'];
        }
        unset($datos);

        // Determinar si aplica multa
        $aplicaMulta  = false;
        $motivoMulta  = null;

        foreach ($porSistema as $sis => $datos) {
            if ($datos['deficiencia_n1'] >= self::UMBRAL_DEFICIENCIA_N1) {
                $aplicaMulta = true;
                $motivoMulta = "Módulo {$sis} supera el {" . self::UMBRAL_DEFICIENCIA_N1 . "}% de deficiencia de funcionamiento";
                break;
            }
        }

        if (!$aplicaMulta && $totalPonderado >= self::UMBRAL_DEFICIENCIA_TOTAL) {
            $aplicaMulta = true;
            $motivoMulta = "La deficiencia ponderada del sistema completo ({$totalPonderado}%) supera el " . self::UMBRAL_DEFICIENCIA_TOTAL . "%";
        }

        return [
            'por_sistema'     => $porSistema,
            'total_ponderado' => round($totalPonderado, 5),
            'aplica_multa'    => $aplicaMulta,
            'motivo_multa'    => $motivoMulta,
        ];
    }

    public function montoMulta(): float
    {
        $analisis = $this->analisis();
        if (!$analisis['aplica_multa'] || !$this->factura_monto) {
            return 0.0;
        }
        return round($analisis['total_ponderado'] / 100 * (float) $this->factura_monto, 2);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /** Etiqueta P01, P28, etc. */
    public function getLabelAttribute(): string
    {
        return 'P' . str_pad($this->numero, 2, '0', STR_PAD_LEFT);
    }

    /** Recalcula dias/minutos desde las fechas y guarda */
    public function recalcularTiempos(): void
    {
        if ($this->fecha_inicio && $this->fecha_fin) {
            $dias = $this->fecha_inicio->diffInDays($this->fecha_fin) + 1;
            $this->dias            = $dias;
            $this->minutos_totales = $dias * 24 * 60;
            $this->saveQuietly();
        }
    }
}
