<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class TicketTicketera extends Model
{
    protected $table = 'tickets_ticketera';

    protected $fillable = [
        'incidencia_911_id',
        'codigo_interno',
        'codigo_ticketera',
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

    public function estaEnviado(): bool
    {
        return $this->estado_envio === 'enviado';
    }
}
