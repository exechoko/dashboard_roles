<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Personal extends Model
{
    use SoftDeletes;

    protected $table = 'personals';

    protected $fillable = [
        'nombre',
        'apellido',
        'lp',
        'jerarquia'
    ];

    protected $casts = [
        'lp' => 'string',
    ];

    // 🔎 Scope
    public function scopeActivos($query)
    {
        return $query->whereNull('deleted_at');
    }

    // 🧠 Formato listo para mostrar
    public function getNombreCompletoAttribute()
    {
        return "{$this->jerarquia} {$this->apellido}, {$this->nombre}, L.P. Nº {$this->lp}";
    }
}
