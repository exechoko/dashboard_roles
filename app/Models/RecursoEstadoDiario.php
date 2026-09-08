<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecursoEstadoDiario extends Model
{
    protected $table = 'recurso_estado_diario';

    protected $fillable = ['recurso_id', 'fecha', 'estado_dia', 'motivo', 'user_id'];

    protected $casts = ['fecha' => 'date'];

    public static array $estados = [
        'circula'           => 'Circula',
        'reserva'           => 'Reserva',
        'fuera_de_servicio' => 'Fuera de servicio',
        'otro'              => 'Otro',
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
        return self::$estados[$this->estado_dia] ?? $this->estado_dia;
    }

    public function getBadgeClassAttribute(): string
    {
        return match ($this->estado_dia) {
            'circula'           => 'success',
            'reserva'           => 'info',
            'fuera_de_servicio' => 'danger',
            'otro'              => 'secondary',
            default             => 'light',
        };
    }
}
