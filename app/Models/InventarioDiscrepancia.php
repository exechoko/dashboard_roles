<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventarioDiscrepancia extends Model
{
    use HasFactory;

    public const TIPO_ARMA = 'arma';

    public const TIPO_CHALECO = 'chaleco';

    public const ESTADO_ACTIVA = 'activa';

    public const ESTADO_RESUELTA = 'resuelta';

    protected $fillable = [
        'personal_id',
        'personal911_id',
        'tipo',
        'estado',
        'valor_local',
        'valor_importado',
        'detalles',
        'detectado_en',
        'ultima_deteccion_en',
        'resuelto_en',
        'corregido_por',
        'motivo',
    ];

    protected $casts = [
        'detalles' => 'array',
        'detectado_en' => 'datetime',
        'ultima_deteccion_en' => 'datetime',
        'resuelto_en' => 'datetime',
    ];

    public function personal(): BelongsTo
    {
        return $this->belongsTo(Personal::class);
    }

    public function corregidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'corregido_por');
    }
}
