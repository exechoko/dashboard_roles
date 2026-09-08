<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VehiculoNovedad extends Model
{
    protected $table = 'vehiculo_novedades';

    protected $fillable = [
        'vehiculo_id',
        'user_id',
        'descripcion',
        'km_actuales',
        'fecha_novedad',
        'resuelta',
    ];

    protected $casts = [
        'fecha_novedad' => 'date',
        'resuelta'      => 'boolean',
        'km_actuales'   => 'integer',
    ];

    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function seguimientos(): HasMany
    {
        return $this->hasMany(VehiculoNovedadSeguimiento::class, 'novedad_id')->orderBy('created_at');
    }

    public function adjuntos(): HasMany
    {
        return $this->hasMany(VehiculoNovedadAdjunto::class, 'novedad_id');
    }

    public function scopePendientes($query)
    {
        return $query->where('resuelta', false);
    }

    public function scopeResueltas($query)
    {
        return $query->where('resuelta', true);
    }
}
