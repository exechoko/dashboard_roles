<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ArmeriaMovimiento extends Model
{
    public $timestamps = false;

    protected $table = 'armeria_movimientos';

    protected $fillable = [
        'movable_type',
        'movable_id',
        'accion',
        'ubicacion_anterior',
        'ubicacion_nueva',
        'estado_anterior',
        'estado_nuevo',
        'user_id',
        'comentario',
        'datos_adicionales',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'datos_adicionales' => 'array',
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
            'CARGA' => 'Alta en armería',
            'MODIFICAR' => 'Modificación de datos',
            'CAMBIO_ESTADO' => 'Cambio de estado',
            'ENVIO_JEFATURA' => 'Envío a Jefatura Central',
            'RETORNO_DIVISION' => 'Retorno a División 911',
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
            'CAMBIO_ESTADO' => 'fa-sliders-h',
            'ENVIO_JEFATURA' => 'fa-arrow-right',
            'RETORNO_DIVISION' => 'fa-arrow-left',
            'ELIMINAR' => 'fa-trash',
            'COMENTARIO' => 'fa-comment',
            default => 'fa-circle',
        };
    }

    public function getAccionColorAttribute(): string
    {
        return match ($this->accion) {
            'CARGA' => 'armeria-teal',
            'MODIFICAR' => 'armeria-amber',
            'CAMBIO_ESTADO' => 'armeria-amber',
            'ENVIO_JEFATURA' => 'armeria-indigo',
            'RETORNO_DIVISION' => 'armeria-green',
            'ELIMINAR' => 'armeria-red',
            'COMENTARIO' => 'armeria-slate',
            default => 'armeria-slate',
        };
    }
}
