<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AudioTranscripcion extends Model
{
    protected $table = 'audio_transcripciones';

    protected $fillable = [
        'nombre_archivo',
        'telefono',
        'tipo_emergencia',
        'resumen',
        'transcripcion_json',
    ];

    /**
     * Devuelve el JSON de transcripción ya parseado.
     */
    public function getTranscripcionAttribute(): array
    {
        return json_decode($this->transcripcion_json, true) ?? [];
    }
}
