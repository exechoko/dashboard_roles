<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AlertaMovimiento extends Model
{
    public $timestamps = false;

    protected $table = 'alerta_movimientos';

    protected $fillable = [
        'movable_type',
        'movable_id',
        'accion',
        'estado_anterior',
        'estado_nuevo',
        'user_id',
        'comentario',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function movable(): MorphTo
    {
        return $this->morphTo();
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getAccionLabelAttribute(): string
    {
        return match ($this->accion) {
            'CARGA' => 'Alta del registro',
            'MODIFICAR' => 'Modificación de datos',
            'CAMBIO_ACTIVO' => 'Cambio de estado (activo/inactivo)',
            'ELIMINAR' => 'Eliminación del registro',
            'COMENTARIO' => 'Nota / Comentario',
            default => $this->accion,
        };
    }

    public function getAccionIconAttribute(): string
    {
        return match ($this->accion) {
            'CARGA' => 'fa-plus-circle',
            'MODIFICAR' => 'fa-edit',
            'CAMBIO_ACTIVO' => 'fa-toggle-on',
            'ELIMINAR' => 'fa-trash',
            'COMENTARIO' => 'fa-comment',
            default => 'fa-circle',
        };
    }

    public function getAccionColorAttribute(): string
    {
        return match ($this->accion) {
            'CARGA' => 'text-success',
            'MODIFICAR' => 'text-primary',
            'CAMBIO_ACTIVO' => 'text-warning',
            'ELIMINAR' => 'text-danger',
            'COMENTARIO' => 'text-secondary',
            default => 'text-muted',
        };
    }
}
