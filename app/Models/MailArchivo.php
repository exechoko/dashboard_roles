<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MailArchivo extends Model
{
    protected $table = 'mail_archivos';

    protected $fillable = [
        'buzon_id',
        'nombre_archivo',
        'ruta_absoluta',
        'tamano_bytes',
        'mtime_archivo',
        'estado',
        'mensajes_total',
        'mensajes_nuevos',
        'bytes_procesados',
        'offset_reanudar',
        'error_message',
        'indexado_at',
    ];

    protected $casts = [
        'mtime_archivo' => 'datetime',
        'indexado_at' => 'datetime',
    ];

    public function buzon(): BelongsTo
    {
        return $this->belongsTo(MailBuzon::class, 'buzon_id');
    }

    public function mensajes(): HasMany
    {
        return $this->hasMany(MailMensaje::class, 'archivo_id');
    }

    public function getPorcentajeAvanceAttribute(): int
    {
        if ($this->tamano_bytes <= 0) {
            return 0;
        }

        return (int) min(100, round(($this->bytes_procesados / $this->tamano_bytes) * 100));
    }
}
