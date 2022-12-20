<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Historico extends Model
{
    protected $table = 'historico';

    public function destino(){
        return $this->belongsTo(Destino::class);
    }

    public function flota(){
        return $this->hasMany(FlotaGeneral::class);
    }

    public function equipo(){
        return $this->belongsTo(Equipo::class);
    }

    public function recurso(){
        return $this->belongsTo(Recurso::class);
    }

}
