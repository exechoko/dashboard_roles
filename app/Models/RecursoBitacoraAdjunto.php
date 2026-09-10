<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecursoBitacoraAdjunto extends Model
{
    protected $table = 'recurso_bitacora_adjuntos';

    protected $fillable = [
        'bitacora_id',
        'seguimiento_id',
        'user_id',
        'ruta',
        'nombre_original',
        'mime_type',
        'tamano',
    ];

    protected $casts = [
        'tamano' => 'integer',
    ];

    public function bitacora(): BelongsTo
    {
        return $this->belongsTo(RecursoBitacora::class, 'bitacora_id');
    }

    public function seguimiento(): BelongsTo
    {
        return $this->belongsTo(RecursoBitacoraSeguimiento::class, 'seguimiento_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function esImagen(): bool
    {
        return str_starts_with((string) $this->mime_type, 'image/');
    }

    public function esPdf(): bool
    {
        return $this->mime_type === 'application/pdf';
    }
}
