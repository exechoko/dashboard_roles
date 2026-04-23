<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManualDocumento extends Model
{
    protected $table = 'manuales_documentos';

    protected $fillable = [
        'tipo',
        'nombre_original',
        'nombre_archivo',
        'ruta_archivo',
        'extension',
        'tamano',
        'subido_por',
    ];

    public function uploader()
    {
        return $this->belongsTo(User::class, 'subido_por');
    }

    public function getTamanoFormateadoAttribute(): string
    {
        $bytes = $this->tamano;
        if ($bytes >= 1048576) return round($bytes / 1048576, 2) . ' MB';
        if ($bytes >= 1024)    return round($bytes / 1024, 2) . ' KB';
        return $bytes . ' B';
    }
}
