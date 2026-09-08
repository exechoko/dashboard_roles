<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehiculo extends Model
{
    protected $table = 'vehiculos';

    public function recurso(){
        return $this->hasMany(Recurso::class);
    }

    public function auditoria(){
        return $this->hasMany(Auditoria::class);
    }

    public function estadoSeccion(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(VehiculoEstadoSeccion::class);
    }

    public function novedades(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(VehiculoNovedad::class)->orderByDesc('fecha_novedad');
    }

    public function novedadesPendientes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(VehiculoNovedad::class)->where('resuelta', false);
    }

    public function prestamos(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(VehiculoPrestamo::class);
    }

    public function prestamoActivo(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(VehiculoPrestamo::class)->where('activo', true);
    }

    public function dotaciones(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(VehiculoDotacion::class);
    }

    public function estadoDiario(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(VehiculoEstadoDiario::class);
    }

    public function estadoDiarioHoy(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(VehiculoEstadoDiario::class)->whereDate('fecha', today());
    }
}
