<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecursoEstadoDiario extends Model
{
    protected $table = 'recurso_estado_diario';

    protected $fillable = ['recurso_id', 'guardia', 'horario', 'fecha_inicio', 'fecha_fin', 'estado_dia', 'motivo', 'user_id'];

    protected $casts = ['fecha_inicio' => 'datetime', 'fecha_fin' => 'datetime'];

    public static array $estados = [
        'circula'           => 'Circula',
        'reserva'           => 'Reserva',
        'fuera_de_servicio' => 'Fuera de servicio',
        'otro'              => 'Otro',
    ];

    public static array $guardias = [
        'guardia_1' => 'Guardia 1',
        'guardia_2' => 'Guardia 2',
        'guardia_3' => 'Guardia 3',
        'guardia_4' => 'Guardia 4',
    ];

    public static array $horarios = [
        '07_19' => '07:00 a 19:00',
        '19_07' => '19:00 a 07:00',
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
