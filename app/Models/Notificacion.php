<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notificacion extends Model
{
    use HasFactory;

    protected $table = 'notificaciones';

    public const CATEGORIA_INFRAESTRUCTURA = 'infraestructura';

    public const CATEGORIA_CAMARAS_CCTV = 'camaras_cctv';

    public const TIPO_ALERTA = 'alerta';

    public const TIPO_RECUPERACION = 'recuperacion';

    protected $fillable = [
        'categoria',
        'tipo',
        'nivel',
        'titulo',
        'mensaje',
        'dispositivo_edificio_id',
        'datos',
    ];

    protected $casts = [
        'datos' => 'array',
    ];

    public function dispositivo(): BelongsTo
    {
        return $this->belongsTo(DispositivoEdificio::class, 'dispositivo_edificio_id');
    }

    public function scopeCategoria(Builder $query, string $categoria): Builder
    {
        return $query->where('categoria', $categoria);
    }

    /**
     * Todo lo que se muestra en el panel de notificaciones de Infraestructura:
     * el monitoreo ping+SNMP del edificio y el resumen de cámaras 911 (CCTV).
     */
    public function scopeDeInfraestructura(Builder $query): Builder
    {
        return $query->whereIn('categoria', [self::CATEGORIA_INFRAESTRUCTURA, self::CATEGORIA_CAMARAS_CCTV]);
    }

    public function scopeNoLeidasDesde(Builder $query, ?string $fecha): Builder
    {
        return $fecha ? $query->where('created_at', '>', $fecha) : $query;
    }
}
