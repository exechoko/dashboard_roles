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
}
