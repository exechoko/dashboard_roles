<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Recurso extends Model
{
    protected $table = 'recursos';

    public function vehiculo(){
        return $this->belongsTo(Vehiculo::class);
    }

    public function destino(){
        return $this->belongsTo(Destino::class);
    }

    public function flota_general(){
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

    public function historico(){
        return $this->hasMany(Historico::class);
    }

    public function auditoria(){
        return $this->hasMany(Auditoria::class);
    }
}
