<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Chaleco extends Model
{
    use HasFactory;

    protected $fillable = [
        'numero_serie',
        'marca',
        'modelo',
        'talle',
        'nivel',
        'lote',
        'origen',
        'observacion_origen',
    ];

    public function asignaciones(): HasMany
    {
        return $this->hasMany(PersonalChalecoAsignacion::class);
    }

    public function retenciones(): HasMany
    {
        return $this->hasMany(ArmaRetencion::class);
    }
}
