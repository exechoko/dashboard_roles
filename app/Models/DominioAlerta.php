<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class DominioAlerta extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'dominios_alerta';

    protected $fillable = [
        'dominio',
        'parcial',
        'marca',
        'modelo',
        'color',
        'motivo',
        'solicitado_por',
        'funcionario_carga',
        'camara_texto',
        'fecha_hecho',
        'fecha_carga',
        'activo',
        'observaciones',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'parcial' => 'boolean',
        'fecha_hecho' => 'date',
        'fecha_carga' => 'date',
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
        return $this->morphMany(AlertaMovimiento::class, 'movable')->orderByDesc('created_at');
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopeInactivos($query)
    {
        return $query->where('activo', false);
    }

    public function getEstadoLabelAttribute(): string
    {
        return $this->activo ? 'Activo' : 'Inactivo';
    }

    /**
     * Normaliza el dominio (patente) a mayúsculas y sin espacios ni
     * guiones, para que la detección de duplicados sea confiable.
     */
    public static function normalizar(mixed $dominio): string
    {
        return strtoupper(preg_replace('/[\s\-]+/', '', trim((string) $dominio)));
    }
}
