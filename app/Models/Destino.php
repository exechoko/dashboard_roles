<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Destino extends Model
{
    use HasFactory;

    protected $table = 'destino';

    protected $fillable = [
        'nombre',
        'tipo',
        'parent_id',
    ];

    // Relación hacia el destino padre
    public function padre()
    {
        return $this->belongsTo(Destino::class, 'parent_id');
    }

    // Relación hacia los destinos hijos
    public function hijos()
    {
        return $this->hasMany(Destino::class, 'parent_id');
    }

    // Devuelve el nombre del destino del que depende (o "Jefatura..." por defecto)
    public function dependeDe()
    {
        return $this->padre->nombre ?? 'Jefatura de Policía de Entre Ríos';
    }
}
