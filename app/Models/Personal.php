<?php

namespace App\Models;

use App\Models\User;
use App\Models\ArmaTipo;
use App\Models\ArmasAnterior;
use App\Models\ArmaRetencion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

class Personal extends Model
{
    use SoftDeletes;

    protected $table = 'personals';

    protected $fillable = [
        'personal911_id',
        'nombre',
        'apellido',
        'lp',
        'dni',
        'jerarquia',
        'numeracion_arma',
        'arma_tipo_id',
        'nro_chaleco',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'lp' => 'string',
        'arma_importacion_bloqueada' => 'boolean',
        'chaleco_importacion_bloqueada' => 'boolean',
        'inventario_bloqueado_en' => 'datetime',
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

    public function armaAsignacionActual(): HasOne
    {
        return $this->hasOne(PersonalArmaAsignacion::class)->where('activa', true);
    }

    public function chalecoAsignacionActual(): HasOne
    {
        return $this->hasOne(PersonalChalecoAsignacion::class)->where('activa', true);
    }

    public function discrepanciasInventario(): HasMany
    {
        return $this->hasMany(InventarioDiscrepancia::class);
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
        if ($this->personal911_id !== null) {
            return $this->armaAsignacionActual()->exists();
        }

        return !empty($this->numeracion_arma) && !empty($this->arma_tipo_id);
    }

    public function tieneRetencionActiva(): bool
    {
        return $this->retenciones()
            ->whereIn('estado', ['EN_ARMERIA', 'EN_JEF_CENTRAL'])
            ->exists();
    }

    public function cambiarArma(string $numeracion, int $tipoId, ?string $chaleco, string $fecha, string $motivo, bool $protegerImportacion = false, ?int $corregidoPor = null): void
    {
        $numeracion = trim($numeracion);
        $chaleco = $chaleco !== null && trim($chaleco) !== '' ? trim($chaleco) : null;
        $arma = Arma::firstOrNew(['numero' => $numeracion]);
        $asignacionArmaAjena = $arma->exists
            ? PersonalArmaAsignacion::query()
                ->where('arma_id', $arma->id)
                ->where('activa', true)
                ->where('personal_id', '<>', $this->id)
                ->exists()
            : false;

        if ($asignacionArmaAjena) {
            throw ValidationException::withMessages([
                'numeracion_arma' => 'El arma ya está asignada a otro funcionario.',
            ]);
        }

        $chalecoModel = null;
        if ($chaleco !== null && trim($chaleco) !== '') {
            $chalecoModel = Chaleco::firstOrNew(['numero_serie' => trim($chaleco)]);
            $asignacionChalecoAjena = $chalecoModel->exists
                ? PersonalChalecoAsignacion::query()
                    ->where('chaleco_id', $chalecoModel->id)
                    ->where('activa', true)
                    ->where('personal_id', '<>', $this->id)
                    ->exists()
                : false;

            if ($asignacionChalecoAjena) {
                throw ValidationException::withMessages([
                    'nro_chaleco' => 'El chaleco ya está asignado a otro funcionario.',
                ]);
            }
        }

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

        if ($protegerImportacion && $this->personal911_id !== null) {
            $this->forceFill([
                'arma_importacion_bloqueada' => true,
                'chaleco_importacion_bloqueada' => true,
                'inventario_bloqueado_por' => $corregidoPor,
                'inventario_bloqueado_en' => now(),
                'inventario_bloqueo_motivo' => $motivo,
            ])->save();
        }

        $arma->arma_tipo_id = $tipoId;
        if (!$arma->exists) {
            $arma->origen = 'manual';
        }
        $arma->save();

        PersonalArmaAsignacion::query()
            ->where('activa', true)
            ->where(function ($query) use ($arma) {
                $query->where('personal_id', $this->id)->orWhere('arma_id', $arma->id);
            })
            ->update(['fecha_hasta' => $fecha, 'activa' => null]);

        PersonalArmaAsignacion::create([
            'personal_id' => $this->id,
            'arma_id' => $arma->id,
            'fecha_desde' => $fecha,
            'activa' => true,
            'origen' => 'manual',
        ]);

        $this->chalecoAsignacionActual()->update(['fecha_hasta' => $fecha, 'activa' => null]);

        if ($chaleco !== null && trim($chaleco) !== '') {
            if (!$chalecoModel->exists) {
                $chalecoModel->origen = 'manual';
                $chalecoModel->save();
            }

            PersonalChalecoAsignacion::create([
                'personal_id' => $this->id,
                'chaleco_id' => $chalecoModel->id,
                'fecha_desde' => $fecha,
                'activa' => true,
                'origen' => 'manual',
            ]);
        }
    }
}
