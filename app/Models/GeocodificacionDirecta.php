<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeocodificacionDirecta extends Model
{
    protected $table = 'geocodificacion_directa';

    protected $fillable = [
        'direccion_original',
        'direccion_normalizada',
        'latitud',
        'longitud',
        'fuente',
    ];
}
