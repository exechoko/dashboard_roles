<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonalSeccion extends Model
{
    protected $table = 'personal_secciones';

    public const MOTIVO_CAMBIO_SECCION = 'cambio_seccion';

    public const MOTIVO_BAJA_POLICIAL = 'baja_policial';

    protected $fillable = [
        'personal_id',
        'id_lugar_personal911',
        'seccion',
        'activo',
        'en_licencia',
        'funcion_actual',
        'fecha_alta',
        'fecha_baja',
        'motivo_baja',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'en_licencia' => 'boolean',
        'fecha_alta' => 'date',
        'fecha_baja' => 'date',
    ];

    public function personal(): BelongsTo
    {
        return $this->belongsTo(Personal::class)->withTrashed();
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    public function scopeEnSecciones(Builder $query, array $secciones): Builder
    {
        if ($secciones === []) {
            return $query;
        }

        return $query->whereIn('seccion', $secciones);
    }

    public function estadoLabel(): string
    {
        if (!$this->activo) {
            return $this->motivo_baja === self::MOTIVO_BAJA_POLICIAL ? 'Baja policial' : 'Dejó la sección';
        }

        return $this->en_licencia ? 'En licencia' : 'Activo';
    }
}
