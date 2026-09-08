<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecursoNovedad extends Model
{
    protected $table = 'recurso_novedades';

    protected $fillable = [
        'recurso_id',
        'tipo',
        'vehiculo_id_referencia',
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

    public function recurso(): BelongsTo
    {
        return $this->belongsTo(Recurso::class);
    }

    public function vehiculoReferencia(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class, 'vehiculo_id_referencia');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function seguimientos(): HasMany
    {
        return $this->hasMany(RecursoNovedadSeguimiento::class, 'novedad_id')->orderBy('created_at');
    }

    public function adjuntos(): HasMany
    {
        return $this->hasMany(RecursoNovedadAdjunto::class, 'novedad_id');
    }

    public function scopePendientes($query)
    {
        return $query->where('resuelta', false);
    }
}
