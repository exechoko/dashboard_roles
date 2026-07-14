<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArmaRetencionHistorial extends Model
{
    public $timestamps = false;

    protected $table = 'arma_retencion_historial';

    protected $fillable = [
        'arma_retencion_id',
        'accion',
        'user_id',
        'comentario',
        'datos_adicionales',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'datos_adicionales' => 'array',
    ];

    public function retencion(): BelongsTo
    {
        return $this->belongsTo(ArmaRetencion::class, 'arma_retencion_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getAccionLabelAttribute(): string
    {
        return match ($this->accion) {
            'CREAR' => 'Creación del registro',
            'MODIFICAR' => 'Modificación',
            'ELEVAR' => 'Elevación a Jef. Central',
            'DEVOLVER' => 'Devolución al funcionario',
            'ELIMINAR' => 'Eliminación del registro',
            'COMENTARIO' => 'Nota / Comentario',
            default => $this->accion,
        };
    }

    public function getAccionIconAttribute(): string
    {
        return match ($this->accion) {
            'CREAR' => 'fa-plus-circle',
            'MODIFICAR' => 'fa-edit',
            'ELEVAR' => 'fa-arrow-up',
            'DEVOLVER' => 'fa-undo',
            'ELIMINAR' => 'fa-trash',
            'COMENTARIO' => 'fa-comment',
            default => 'fa-circle',
        };
    }

    public function getAccionColorAttribute(): string
    {
        return match ($this->accion) {
            'CREAR' => 'info',
            'MODIFICAR' => 'warning',
            'ELEVAR' => 'primary',
            'DEVOLVER' => 'success',
            'ELIMINAR' => 'danger',
            'COMENTARIO' => 'secondary',
            default => 'secondary',
        };
    }
}
