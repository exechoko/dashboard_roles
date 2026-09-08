<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Recurso extends Model
{
    protected $table = 'recursos';

    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class);
    }

    public function destino(): BelongsTo
    {
        return $this->belongsTo(Destino::class);
    }

    public function flota_general(): HasMany
    {
        return $this->hasMany(FlotaGeneral::class);
    }

    public function flotaActiva(): HasMany
    {
        return $this->hasMany(FlotaGeneral::class)->whereNull('fecha_desasignacion');
    }

    public function cecocoAliases(): HasMany
    {
        return $this->hasMany(CecocoRecursoAlias::class);
    }

    public function historico(): HasMany
    {
        return $this->hasMany(Historico::class);
    }

    public function auditoria(): HasMany
    {
        return $this->hasMany(Auditoria::class);
    }

    // ─── Flota 911 ───────────────────────────────────────────

    public function asignacionesVehiculo(): HasMany
    {
        return $this->hasMany(RecursoVehiculoAsignacion::class)->orderByDesc('fecha_desde');
    }

    public function asignacionActual(): HasOne
    {
        return $this->hasOne(RecursoVehiculoAsignacion::class)->whereNull('fecha_hasta');
    }

    public function vehiculoActual(): ?Vehiculo
    {
        return $this->asignacionActual?->vehiculo ?? $this->vehiculo;
    }

    public function novedades(): HasMany
    {
        return $this->hasMany(RecursoNovedad::class)->orderByDesc('fecha_novedad');
    }

    public function novedadesPendientes(): HasMany
    {
        return $this->hasMany(RecursoNovedad::class)->where('resuelta', false);
    }

    public function estadoSeccion(): HasOne
    {
        return $this->hasOne(RecursoEstadoSeccion::class);
    }

    public function prestamos(): HasMany
    {
        return $this->hasMany(RecursoPrestamo::class);
    }

    public function prestamoActivo(): HasOne
    {
        return $this->hasOne(RecursoPrestamo::class)->where('activo', true);
    }

    public function dotaciones(): HasMany
    {
        return $this->hasMany(RecursoDotacion::class);
    }

    public function estadoDiario(): HasMany
    {
        return $this->hasMany(RecursoEstadoDiario::class);
    }

    public function estadoDiarioHoy(): HasOne
    {
        return $this->hasOne(RecursoEstadoDiario::class)->whereDate('fecha_inicio', today())->latest('fecha_inicio');
    }
}
