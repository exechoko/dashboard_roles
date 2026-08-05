<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivacionTotem extends Model
{
    use HasFactory;

    protected $table = 'activaciones_totem';

    public const ESTADO_PENDIENTE = 'pendiente';
    public const ESTADO_DESCARGADO = 'descargado';
    public const ESTADO_DESCARTADO = 'descartado';
    public const ESTADO_ELIMINADO = 'eliminado';

    public const ESTADOS = [
        self::ESTADO_PENDIENTE => 'Pendiente',
        self::ESTADO_DESCARGADO => 'Descargado',
        self::ESTADO_DESCARTADO => 'Descartado',
        self::ESTADO_ELIMINADO => 'Eliminado',
    ];

    /**
     * Máximo legal de retención del video: 6 meses desde el evento.
     */
    public const MESES_RETENCION_LEGAL = 6;

    public const SUBIDA_PENDIENTE = 'pendiente';
    public const SUBIDA_PROCESANDO = 'procesando';
    public const SUBIDA_COMPLETADO = 'completado';
    public const SUBIDA_ERROR = 'error';

    protected $fillable = [
        'evento_cecoco_id',
        'nro_expediente',
        'fecha_evento',
        'palabra_detectada',
        'estado',
        'camara_id',
        'descargado_por',
        'fecha_descarga',
        'observaciones',
        'eliminado_por',
        'fecha_eliminado',
        'nombre_archivo_original',
        'ruta_archivo',
        'hash_sha256',
        'subida_estado',
        'subida_error',
    ];

    protected $casts = [
        'fecha_evento' => 'datetime',
        'fecha_descarga' => 'datetime',
        'fecha_eliminado' => 'datetime',
    ];

    public function evento(): BelongsTo
    {
        return $this->belongsTo(EventoCecoco::class, 'evento_cecoco_id');
    }

    public function camara(): BelongsTo
    {
        return $this->belongsTo(Camara::class, 'camara_id');
    }

    public function descargadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'descargado_por');
    }

    public function eliminadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'eliminado_por');
    }

    /**
     * Vencida: pasaron los 6 meses de retención legal y todavía queda un video
     * por borrar (pendiente de descargar o ya descargado). Los descartados
     * (ruido) y los ya eliminados quedan fuera.
     */
    public function esVencida(): bool
    {
        return in_array($this->estado, [self::ESTADO_PENDIENTE, self::ESTADO_DESCARGADO], true)
            && $this->fecha_evento->lte(now()->subMonths(self::MESES_RETENCION_LEGAL));
    }

    public function scopeVencidas($query)
    {
        return $query->whereIn('estado', [self::ESTADO_PENDIENTE, self::ESTADO_DESCARGADO])
            ->where('fecha_evento', '<=', now()->subMonths(self::MESES_RETENCION_LEGAL));
    }
}
