<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeocodificacionInversa extends Model
{
    protected $table = 'geocodificacion_inversa';
    protected $fillable = ['latitud', 'longitud', 'direccion'];
}
