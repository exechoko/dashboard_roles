<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TareaItem extends Model
{
    use HasFactory;

    protected $table = 'tarea_items';

    public const ESTADO_PENDIENTE = 'pendiente';
    public const ESTADO_EN_PROCESO = 'en_proceso';
    public const ESTADO_REALIZADA = 'realizada';

    public const ESTADOS = [
        self::ESTADO_PENDIENTE => 'Pendiente',
        self::ESTADO_EN_PROCESO => 'En proceso',
        self::ESTADO_REALIZADA => 'Realizada',
    ];

    protected $fillable = [
        'tarea_id',
        'fecha_programada',
        'estado',
        'realizado_por',
        'fecha_realizada',
        'observaciones',
    ];

    protected $casts = [
        'fecha_programada' => 'date',
        'fecha_realizada' => 'datetime',
    ];

    public function tarea()
    {
        return $this->belongsTo(Tarea::class, 'tarea_id');
    }

    public function realizadoPor()
    {
        return $this->belongsTo(User::class, 'realizado_por');
    }
}
