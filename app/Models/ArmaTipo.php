<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArmaTipo extends Model
{
    protected $table = 'arma_tipos';

    protected $fillable = [
        'nombre',
        'activo',
    ];

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}
