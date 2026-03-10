<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Importacion extends Model
{
    use HasFactory;

    protected $table = 'importaciones';

    protected $fillable = [
        'nombre_archivo',
        'periodo',
        'anio',
        'mes',
        'total_registros',
        'registros_importados',
        'registros_duplicados',
        'registros_omitidos',
        'registros_con_error',
        'estado',
        'errores',
        'tiempo_procesamiento',
    ];

    protected $casts = [
        'anio' => 'integer',
        'mes' => 'integer',
        'total_registros' => 'integer',
        'registros_importados' => 'integer',
        'registros_duplicados' => 'integer',
        'registros_omitidos' => 'integer',
        'registros_con_error' => 'integer',
        'tiempo_procesamiento' => 'integer',
    ];

    public function eventos(): HasMany
    {
        return $this->hasMany(EventoCecoco::class, 'importacion_id');
    }

    public function getNombreArchivoCortoAttribute(): string
    {
        return basename($this->nombre_archivo);
    }

    public function scopeDelAnio($query, int $anio)
    {
        return $query->where('anio', $anio);
    }

    public function scopeDelMes($query, int $mes)
    {
        return $query->where('mes', $mes);
    }
}
