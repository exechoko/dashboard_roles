<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebDependencia extends Model
{
    use HasFactory;

    protected $table = 'web_dependencias';

    protected $fillable = [
        'nombre',
        'categoria',
        'direccion',
        'telefonos',
        'tags',
        'orden',
    ];

    protected $casts = [
        'telefonos' => 'array',
        'tags'      => 'array',
    ];
}
