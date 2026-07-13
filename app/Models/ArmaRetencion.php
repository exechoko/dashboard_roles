<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ArmaRetencion extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'arma_retenciones';

    protected $fillable = [
        'personal_id',
        'arma_id',
        'chaleco_id',
        'arma_numero',
        'arma_tipo',
        'chaleco_numero',
        'chaleco_detalle',
        'tipo',
        'motivo_id',
        'fecha_posesion',
        'dias_restantes',
        'fecha_elevacion',
        'fecha_devolucion',
        'observaciones',
        'estado',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'fecha_posesion' => 'date',
        'fecha_elevacion' => 'date',
        'fecha_devolucion' => 'date',
        'dias_restantes' => 'integer',
    ];

    public function personal(): BelongsTo
    {
        return $this->belongsTo(Personal::class)->withTrashed();
    }

    public function motivo(): BelongsTo
    {
        return $this->belongsTo(ArmaMotivo::class, 'motivo_id');
    }

    public function arma(): BelongsTo
    {
        return $this->belongsTo(Arma::class);
    }

    public function chaleco(): BelongsTo
    {
        return $this->belongsTo(Chaleco::class);
    }

    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function actualizadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeEnArmeria($query)
    {
        return $query->where('estado', 'EN_ARMERIA');
    }

    public function scopeEnJefCentral($query)
    {
        return $query->where('estado', 'EN_JEF_CENTRAL');
    }

    public function scopeDevueltas($query)
    {
        return $query->where('estado', 'DEVUELTA');
    }

    public function scopeActivas($query)
    {
        return $query->whereIn('estado', ['EN_ARMERIA', 'EN_JEF_CENTRAL']);
    }

    public function getEstadoLabelAttribute(): string
    {
        return match ($this->estado) {
            'EN_ARMERIA' => 'En Armería Patrulla',
            'EN_JEF_CENTRAL' => 'En Jef. Central',
            'DEVUELTA' => 'Devuelta a Funcionario',
            default => $this->estado,
        };
    }

    public function getTipoLabelAttribute(): string
    {
        return match ($this->tipo) {
            'RETENCIÓN' => 'Retención',
            'REGULACIÓN' => 'Regulación',
            'RESGUARDO' => 'Resguardo',
            default => $this->tipo,
        };
    }
}
