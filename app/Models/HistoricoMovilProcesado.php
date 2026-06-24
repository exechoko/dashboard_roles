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

    /**
     * Las columnas fecha_inicio/fecha_fin son string y guardan el rango ya
     * formateado para mostrar (ej. "23/06/2026 00:00:00"). No deben castearse a
     * datetime: Carbon no puede parsear el formato d/m/Y y lanza
     * InvalidFormatException al guardar (rompía "Procesar Histórico GIS").
     */
    protected $casts = [
        'metadata' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
