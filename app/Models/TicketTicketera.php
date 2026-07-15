<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class TicketTicketera extends Model
{
    protected $table = 'tickets_ticketera';

    protected $fillable = [
        'incidencia_911_id',
        'codigo_interno',
        'codigo_ticketera',
        'referencia_ticketera',
        'url_seguimiento',
        'asunto',
        'texto_enviado',
        'tipo_equipo',
        'modelo_equipo',
        'movil',
        'recurso_id',
        'equipo_id',
        'tipo_terminal_id',
        'dependencia',
        'oficina',
        'problema_detectado',
        'fecha_inicio_falla',
        'fecha_fin_falla',
        'prioridad',
        'subsistema',
        'camaras_afectadas',
        'cantidad_items',
        'periodo_facturado',
        'aplica_calculo',
        'observaciones',
        'estado_envio',
        'estado_ticketera',
        'enviado_en',
        'ultimo_error',
    ];

    protected $casts = [
        'aplica_calculo'     => 'boolean',
        'enviado_en'         => 'datetime',
        'fecha_inicio_falla' => 'datetime',
        'fecha_fin_falla'    => 'datetime',
        'camaras_afectadas'  => 'array',
    ];

    public function incidencia911(): BelongsTo
    {
        return $this->belongsTo(Incidencia911::class, 'incidencia_911_id');
    }

    public function recurso(): BelongsTo
    {
        return $this->belongsTo(Recurso::class, 'recurso_id');
    }

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class, 'equipo_id');
    }

    public function tipoTerminal(): BelongsTo
    {
        return $this->belongsTo(TipoTerminal::class, 'tipo_terminal_id');
    }

    /**
     * Filtra por grupo de estado de la ticketera: 'nuevos', 'en_progreso'
     * (incluye en espera y respondido) o 'resueltos'. Agrupa las variantes
     * que vienen del Excel/HESK con el mismo criterio que colorEstadoTicketera().
     */
    public function scopeGrupoEstado(Builder $query, string $grupo): Builder
    {
        return match ($grupo) {
            'nuevos'      => $query->where(function (Builder $condiciones): void {
                $condiciones->whereNull('estado_ticketera')
                    ->orWhere('estado_ticketera', 'Nuevo')
                    ->orWhere('estado_ticketera', 'creado');
            }),
            'en_progreso' => $query->where(function (Builder $condiciones): void {
                $condiciones->where('estado_ticketera', 'like', '%progre%')
                    ->orWhere('estado_ticketera', 'like', '%espera%')
                    ->orWhere('estado_ticketera', 'like', '%respond%');
            }),
            'resueltos'   => $query->where(function (Builder $condiciones): void {
                $condiciones->where('estado_ticketera', 'like', '%resuel%')
                    ->orWhere('estado_ticketera', 'like', '%cierre%');
            }),
            default       => $query,
        };
    }

    public function estaEnviado(): bool
    {
        return $this->estado_envio === 'enviado';
    }

    public function yaEstaEnTicketera(): bool
    {
        return !empty($this->codigo_ticketera);
    }

    public function estadoTicketeraLegible(): string
    {
        return $this->estado_ticketera ?: 'Nuevo';
    }

    /**
     * Color de badge Bootstrap según el estado del ticket en la ticketera
     * (Resuelto / En progreso / Nuevo / etc., tal como viene del Excel o HESK).
     */
    public function colorEstadoTicketera(): string
    {
        $estado = mb_strtolower($this->estadoTicketeraLegible());

        return match (true) {
            str_contains($estado, 'resuel') || str_contains($estado, 'cierre') => 'success',
            str_contains($estado, 'progre') || str_contains($estado, 'espera') => 'warning',
            str_contains($estado, 'respond')                                   => 'info',
            default                                                            => 'primary',
        };
    }
}
