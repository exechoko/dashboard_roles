<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function historico(){
        return $this->hasMany(Historico::class);
    }
}
