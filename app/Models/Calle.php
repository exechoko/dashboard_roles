<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Calle extends Model
{
    protected $table = 'calles';

    protected $fillable = [
        'georef_id',
        'calle',
        'tipo',
        'calle_normalizada',
        'altura_inicio',
        'altura_fin',
        'localidad',
        'localidad_id',
        'provincia',
        'cp',
        'user',
        'geometria',
    ];

    protected $casts = [
        'geometria' => 'array',
    ];
}
