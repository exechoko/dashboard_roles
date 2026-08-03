<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ArmeriaArma extends Model
{
    use HasFactory, SoftDeletes;

    public const TIPOS = ['ESCOPETA', 'SUBFUSIL', 'FUSIL', 'PISTOLA', 'OTRO'];

    public const ESTADOS = ['EN_SERVICIO', 'EN_REPARACION', 'DE_BAJA'];

    public const UBICACIONES = ['DIVISION_911', 'JEFATURA_CENTRAL'];

    protected $table = 'armeria_armas';

    protected $fillable = [
        'tipo',
        'marca',
        'modelo',
        'numero_serie',
        'estado',
        'ubicacion',
        'observaciones',
        'created_by',
        'updated_by',
    ];

    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function actualizadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function movimientos(): MorphMany
    {
        return $this->morphMany(ArmeriaMovimiento::class, 'movable')->orderByDesc('created_at');
    }

    public function adjuntos(): MorphMany
    {
        return $this->morphMany(ArmeriaAdjunto::class, 'adjuntable')->orderByDesc('created_at');
    }

    public function scopeEnDivision($query)
    {
        return $query->where('ubicacion', 'DIVISION_911');
    }

    public function scopeEnJefaturaCentral($query)
    {
        return $query->where('ubicacion', 'JEFATURA_CENTRAL');
    }

    public function getEstadoLabelAttribute(): string
    {
        return match ($this->estado) {
            'EN_SERVICIO' => 'En Servicio',
            'EN_REPARACION' => 'En Reparación',
            'DE_BAJA' => 'De Baja',
            default => $this->estado,
        };
    }

    public function getUbicacionLabelAttribute(): string
    {
        return match ($this->ubicacion) {
            'DIVISION_911' => 'Armería División 911',
            'JEFATURA_CENTRAL' => 'Armería Jefatura Central',
            default => $this->ubicacion,
        };
    }
}
