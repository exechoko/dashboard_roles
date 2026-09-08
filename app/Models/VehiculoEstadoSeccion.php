<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehiculoEstadoSeccion extends Model
{
    protected $table = 'vehiculo_estados_seccion';

    protected $fillable = ['vehiculo_id', 'estado', 'observaciones', 'user_id'];

    public static array $estados = [
        'en_servicio'       => 'En servicio',
        'fuera_de_servicio' => 'Fuera de servicio',
        'en_taller'         => 'En taller',
        'baja_provisional'  => 'Baja provisional',
    ];

    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class);
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
