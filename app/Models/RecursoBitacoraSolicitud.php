<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecursoBitacoraSolicitud extends Model
{
    public const TIPO_EDICION = 'edicion';
    public const TIPO_ELIMINACION = 'eliminacion';

    public const ESTADO_PENDIENTE = 'pendiente';
    public const ESTADO_APROBADA = 'aprobada';
    public const ESTADO_RECHAZADA = 'rechazada';

    protected $table = 'recurso_bitacora_solicitudes';

    protected $fillable = [
        'bitacora_id',
        'tipo',
        'cambios',
        'estado',
        'motivo',
        'motivo_resolucion',
        'user_id',
        'resuelta_por',
        'resuelta_en',
    ];

    protected $casts = [
        'cambios'     => 'array',
        'resuelta_en' => 'datetime',
    ];

    public function bitacora(): BelongsTo
    {
        return $this->belongsTo(RecursoBitacora::class, 'bitacora_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function resueltaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resuelta_por');
    }

    public function scopePendientes(Builder $query): Builder
    {
        return $query->where('estado', self::ESTADO_PENDIENTE);
    }

    public function scopeResueltas(Builder $query): Builder
    {
        return $query->whereIn('estado', [self::ESTADO_APROBADA, self::ESTADO_RECHAZADA]);
    }

    public function estaPendiente(): bool
    {
        return $this->estado === self::ESTADO_PENDIENTE;
    }
}
