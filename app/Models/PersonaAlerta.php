<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class PersonaAlerta extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'personas_alerta';

    protected $fillable = [
        'dni',
        'apellido_nombre',
        'direccion',
        'motivo',
        'solicitado_por',
        'funcionario_carga',
        'notificar_a',
        'identificado',
        'finalizado',
        'foto',
        'fecha_hecho',
        'fecha_carga',
        'activo',
        'observaciones',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'identificado' => 'boolean',
        'finalizado' => 'boolean',
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

    public function getFotoUrlAttribute(): ?string
    {
        return $this->foto ? Storage::disk('anexos')->url($this->foto) : null;
    }

    /**
     * Normaliza el D.N.I. quitando puntos y espacios, para que la
     * detección de duplicados sea confiable (ej. "30.111.222" => "30111222").
     */
    public static function normalizarDni(mixed $dni): ?string
    {
        $dni = trim((string) $dni);

        if ($dni === '' || $dni === '-') {
            return null;
        }

        $dni = preg_replace('/[.\s]+/', '', $dni);

        return $dni === '' ? null : $dni;
    }
}
