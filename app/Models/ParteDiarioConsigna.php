<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParteDiarioConsigna extends Model
{
    use HasFactory;

    protected $table = 'parte_diario_consignas';

    protected $fillable = [
        'grupo',
        'nombre',
        'orden',
        'activa',
    ];

    protected $casts = [
        'orden'  => 'integer',
        'activa' => 'boolean',
    ];

    public function scopeActivas(Builder $query): Builder
    {
        return $query->where('activa', true);
    }

    public function scopeOrdenadas(Builder $query): Builder
    {
        return $query->orderBy('orden')->orderBy('id');
    }
}
