<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Antena extends Model
{
    use HasFactory;

    protected $table = 'antenas';

    protected $fillable = [
        'nombre',
        'localidad',
        'ubicacion',
        'latitud',
        'longitud',
        'altura',
        'activa',
        'observaciones',
    ];

    protected $casts = [
        'activa' => 'boolean',
        'latitud' => 'double',
        'longitud' => 'double',
        'altura' => 'double',
    ];
}
