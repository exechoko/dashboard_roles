<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SecuenciaTicketera extends Model
{
    protected $table = 'secuencias_ticketera';

    protected $fillable = [
        'anio',
        'ultimo_numero',
    ];
}
