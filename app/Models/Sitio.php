<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sitio extends Model
{
    public const ENERGIZADO_POR = [
        'ENERSA',
        'Mun. Paraná',
        'Mun. Cnia. Avellaneda',
        'Mun. Oro Verde',
        'Mun. San Benito',
    ];

    protected $table = 'sitio';
    protected $fillable = ['nombre', 'latitud', 'longitud', 'localidad', 'cartel', 'activo', 'energizado_por'];

    public function destino(){
        return $this->belongsTo(Destino::class);
    }

    public function auditoria(){
        return $this->hasMany(Auditoria::class);
    }

    public function camara(){
        return $this->hasMany(Camara::class);
    }

}
