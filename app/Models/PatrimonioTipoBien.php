<?php
// app/Models/PatrimonioTipoBien.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PatrimonioTipoBien extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'patrimonio_tipos_bien';

    protected $fillable = [
        'nombre',
        'tiene_tabla_propia',
        'tabla_referencia',
        'descripcion',
    ];

    protected $casts = [
        'tiene_tabla_propia' => 'boolean',
    ];

    /**
     * Relación con bienes
     */
    public function bienes()
    {
        return $this->hasMany(PatrimonioBien::class, 'tipo_bien_id');
    }

    /**
     * Scope para tipos con tabla propia
     */
    public function scopeConTablaPropia($query)
    {
        return $query->where('tiene_tabla_propia', true);
    }

    /**
     * Scope para tipos sin tabla propia
     */
    public function scopeSinTablaPropia($query)
    {
        return $query->where('tiene_tabla_propia', false);
    }

    /**
     * Obtener cantidad de bienes activos
     */
    public function getBienesActivosCountAttribute()
    {
        return $this->bienes()->where('estado', 'activo')->count();
    }
}
