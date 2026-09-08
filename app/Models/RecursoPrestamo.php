<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecursoPrestamo extends Model
{
    protected $table = 'recurso_prestamos';

    protected $fillable = [
        'recurso_id',
        'vehiculo_id_snapshot',
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

    public function recurso(): BelongsTo
    {
        return $this->belongsTo(Recurso::class);
    }

    public function vehiculoSnapshot(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class, 'vehiculo_id_snapshot');
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
