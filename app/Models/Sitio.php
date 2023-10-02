<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sitio extends Model
{
    protected $table = 'sitio';
    protected $fillable = ['nombre', 'latitud', 'longitud', 'localidad'];

    public function destino(){
        return $this->belongsTo(Destino::class);
    }

    public function auditoria(){
        return $this->hasMany(Auditoria::class);
    }

    public function camara(){
        return $this->hasMany(Camara::class);
    }

}
