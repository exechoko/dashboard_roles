<?php

namespace App\Models;

use App\Models\User;
use App\Models\ArmaTipo;
use App\Models\ArmasAnterior;
use App\Models\ArmaRetencion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Personal extends Model
{
    use SoftDeletes;

    protected $table = 'personals';

    protected $fillable = [
        'nombre',
        'apellido',
        'lp',
        'jerarquia',
        'numeracion_arma',
        'arma_tipo_id',
        'nro_chaleco',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'lp' => 'string',
    ];

    // 🔎 Scope
    public function scopeActivos($query)
    {
        return $query->whereNull('deleted_at');
    }

    // 🧠 Formato listo para mostrar
    public function getNombreCompletoAttribute()
    {
        return "{$this->jerarquia} {$this->apellido}, {$this->nombre}, L.P. Nº {$this->lp}";
    }

    public function retenciones(): HasMany
    {
        return $this->hasMany(ArmaRetencion::class);
    }

    public function tipoArma(): BelongsTo
    {
        return $this->belongsTo(ArmaTipo::class, 'arma_tipo_id');
    }

    public function armasAnteriores(): HasMany
    {
        return $this->hasMany(ArmasAnterior::class)->orderByDesc('fecha_cambio');
    }

    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function actualizadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function tieneArmaAsignada(): bool
    {
        return !empty($this->numeracion_arma) && !empty($this->arma_tipo_id);
    }

    public function tieneRetencionActiva(): bool
    {
        return $this->retenciones()
            ->whereIn('estado', ['EN_ARMERIA', 'EN_JEF_CENTRAL'])
            ->exists();
    }

    public function cambiarArma(string $numeracion, int $tipoId, ?string $chaleco, string $fecha, string $motivo): void
    {
        if ($this->tieneArmaAsignada()) {
            ArmasAnterior::create([
                'personal_id' => $this->id,
                'numeracion_arma' => $this->numeracion_arma,
                'arma_tipo_id' => $this->arma_tipo_id,
                'nro_chaleco' => $this->nro_chaleco,
                'fecha_cambio' => $fecha,
                'motivo_cambio' => $motivo,
                'created_by' => auth()->id(),
            ]);
        }

        $this->update([
            'numeracion_arma' => $numeracion,
            'arma_tipo_id' => $tipoId,
            'nro_chaleco' => $chaleco,
        ]);
    }
}
