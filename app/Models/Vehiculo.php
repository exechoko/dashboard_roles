<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Vehiculo extends Model
{
    protected $table = 'vehiculos';

    public function recurso(): HasMany
    {
        return $this->hasMany(Recurso::class);
    }

    public function auditoria(): HasMany
    {
        return $this->hasMany(Auditoria::class);
    }

    public function asignacionesRecurso(): HasMany
    {
        return $this->hasMany(RecursoVehiculoAsignacion::class)->orderByDesc('fecha_desde');
    }

    public function asignacionActual(): HasOne
    {
        return $this->hasOne(RecursoVehiculoAsignacion::class)->whereNull('fecha_hasta');
    }
}
