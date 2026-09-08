<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehiculoPrestamo extends Model
{
    protected $table = 'vehiculo_prestamos';

    protected $fillable = [
        'vehiculo_id',
        'destino_origen_id',
        'destino_destino_id',
        'fecha_salida',
        'fecha_retorno',
        'observaciones_salida',
        'observaciones_retorno',
        'user_id_prestamo',
        'user_id_retorno',
        'activo',
    ];

    protected $casts = [
        'fecha_salida'  => 'datetime',
        'fecha_retorno' => 'datetime',
        'activo'        => 'boolean',
    ];

    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class);
    }

    public function destinoOrigen(): BelongsTo
    {
        return $this->belongsTo(Destino::class, 'destino_origen_id');
    }

    public function destinoDestino(): BelongsTo
    {
        return $this->belongsTo(Destino::class, 'destino_destino_id');
    }

    public function usuarioPrestamo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id_prestamo');
    }

    public function usuarioRetorno(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id_retorno');
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}
