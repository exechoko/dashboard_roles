<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArmaMotivo extends Model
{
    use HasFactory;

    protected $table = 'arma_motivos';

    protected $fillable = [
        'nombre',
        'dias',
        'tipo_asignado',
        'activo',
    ];

    protected $casts = [
        'dias' => 'integer',
        'activo' => 'boolean',
    ];

    public function retenciones(): HasMany
    {
        return $this->hasMany(ArmaRetencion::class, 'motivo_id');
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}
