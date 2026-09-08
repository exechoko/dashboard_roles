<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecursoEstadoSeccion extends Model
{
    protected $table = 'recurso_estados_seccion';

    protected $fillable = ['recurso_id', 'estado', 'observaciones', 'user_id'];

    public static array $estados = [
        'en_servicio'       => 'En servicio',
        'fuera_de_servicio' => 'Fuera de servicio',
        'en_taller'         => 'En taller',
        'baja_provisional'  => 'Baja provisional',
    ];

    public function recurso(): BelongsTo
    {
        return $this->belongsTo(Recurso::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getLabelAttribute(): string
    {
        return self::$estados[$this->estado] ?? $this->estado;
    }

    public function getBadgeClassAttribute(): string
    {
        return match ($this->estado) {
            'en_servicio'       => 'success',
            'fuera_de_servicio' => 'danger',
            'en_taller'         => 'warning',
            'baja_provisional'  => 'secondary',
            default             => 'light',
        };
    }
}
