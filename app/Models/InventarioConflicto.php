<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventarioConflicto extends Model
{
    use HasFactory;

    public const TIPO_ARMA = 'arma';

    public const TIPO_CHALECO = 'chaleco';

    public const ESTADO_ACTIVO = 'activo';

    public const ESTADO_RESUELTO = 'resuelto';

    protected $fillable = [
        'tipo',
        'identificador',
        'estado',
        'detalles',
        'detectado_en',
        'ultima_deteccion_en',
        'resuelto_en',
    ];

    protected $casts = [
        'detalles' => 'array',
        'detectado_en' => 'datetime',
        'ultima_deteccion_en' => 'datetime',
        'resuelto_en' => 'datetime',
    ];
}
