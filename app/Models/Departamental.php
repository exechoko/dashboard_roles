<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Departamental extends Model
{
    protected $table = 'departamentales';
    protected $fillable = [
        'nombre'
    ];

    /*public function direccion(){
        return $this->belongsTo(Direccion::class);
    }*/

    public function comisaria(){
        return $this->hasMany(Comisaria::class);
    }

    public function division(){
        return $this->hasMany(Division::class);
    }

    public function seccion(){
        return $this->hasMany(Seccion::class);
    }

    public function destino(){
        return $this->hasMany(Destino::class);
    }
}
