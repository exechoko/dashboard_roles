<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebGaleriaImagen extends Model
{
    use HasFactory;

    protected $table = 'web_galeria_imagenes';

    protected $fillable = [
        'titulo',
        'categoria',
        'imagen',
        'orden',
    ];
}
