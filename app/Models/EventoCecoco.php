<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventoCecoco extends Model
{
    use HasFactory;

    protected $table = 'evento_cecoco';

    protected $fillable = [
        'nro_expediente',
        'fecha_hora',
        'box',
        'operador',
        'descripcion',
        'direccion',
        'telefono',
        'fecha_cierre',
        'tipo_servicio',
        'periodo',
        'anio',
        'mes',
        'importacion_id',
    ];

    protected $casts = [
        'fecha_hora' => 'datetime',
        'fecha_cierre' => 'datetime',
        'anio' => 'integer',
        'mes' => 'integer',
    ];

    public function importacion(): BelongsTo
    {
        return $this->belongsTo(Importacion::class, 'importacion_id');
    }

    public function detalle(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(DetalleExpedienteCecoco::class, 'evento_cecoco_id');
    }

    public function scopeDelPeriodo($query, string $periodo)
    {
        return $query->where('periodo', $periodo);
    }

    public function scopeDelAnio($query, int $anio)
    {
        return $query->where('anio', $anio);
    }

    public function scopeDelMes($query, int $mes)
    {
        return $query->where('mes', $mes);
    }

    public function scopePorOperador($query, string $operador)
    {
        return $query->where('operador', $operador);
    }

    public function scopePorTipo($query, string $tipo)
    {
        return $query->where('tipo_servicio', $tipo);
    }

    public function scopeEntreFechas($query, string $desde, string $hasta)
    {
        return $query->whereBetween('fecha_hora', [$desde . ' 00:00:00', $hasta . ' 23:59:59']);
    }

    public function scopeBuscar($query, string $texto)
    {
        if (is_numeric($texto)) {
            return $query->where(function ($q) use ($texto) {
                $q->where('nro_expediente', $texto)
                    ->orWhere('telefono', $texto);
            });
        }

        return $query->where(function ($q) use ($texto) {
            $q->where('nro_expediente', 'LIKE', "%{$texto}%")
                ->orWhere('direccion', 'LIKE', "%{$texto}%")
                ->orWhere('operador', 'LIKE', "%{$texto}%")
                ->orWhere('tipo_servicio', 'LIKE', "%{$texto}%")
                ->orWhere('descripcion', 'LIKE', "%{$texto}%");
        });
    }
}
