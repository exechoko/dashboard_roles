<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistoricoMovilProcesado extends Model
{
    protected $table = 'historico_movil_procesados';

    protected $fillable = [
        'user_id',
        'nombre_archivo',
        'recurso',
        'fecha_inicio',
        'fecha_fin',
        'posiciones',
        'velocidad_maxima',
        'umbral_naranja',
        'umbral_rojo',
        'metadata',
        'registros_json',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
