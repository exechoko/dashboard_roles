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

    public function activacionTotem(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ActivacionTotem::class, 'evento_cecoco_id');
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

    /**
     * Aplica los filtros de búsqueda de eventos (usado por el buscador de
     * escritorio y por el buscador móvil, para no duplicar la lógica).
     *
     * @param array{anio?: mixed, mes?: mixed, operador?: mixed, tipo?: mixed, tipos?: mixed, desde_datetime?: mixed, hasta_datetime?: mixed, desde?: mixed, hasta?: mixed, hora_desde?: mixed, hora_hasta?: mixed, buscar?: mixed} $filtros
     */
    public function scopeFiltrado($query, array $filtros)
    {
        if (!empty($filtros['anio'])) {
            $query->delAnio((int) $filtros['anio']);
        }

        if (!empty($filtros['mes'])) {
            $query->delMes((int) $filtros['mes']);
        }

        if (!empty($filtros['operador'])) {
            $query->porOperador($filtros['operador']);
        }

        $tipos = self::tiposNormalizados($filtros['tipos'] ?? []);
        if ($tipos !== []) {
            self::aplicarFiltroTipos($query, $tipos);
        } elseif (!empty($filtros['tipo'])) {
            $query->porTipo($filtros['tipo']);
        }

        if (!empty($filtros['desde_datetime']) && !empty($filtros['hasta_datetime'])) {
            $desde = str_replace('T', ' ', $filtros['desde_datetime']);
            $hasta = str_replace('T', ' ', $filtros['hasta_datetime']);

            if (strlen($desde) === 16) {
                $desde .= ':00';
            }

            if (strlen($hasta) === 16) {
                $hasta .= ':59';
            }

            $query->whereBetween('fecha_hora', [$desde, $hasta]);
        } elseif (!empty($filtros['desde']) && !empty($filtros['hasta'])) {
            $desde = $filtros['desde'] . ' ' . (!empty($filtros['hora_desde']) ? $filtros['hora_desde'] : '00:00:00');
            $hasta = $filtros['hasta'] . ' ' . (!empty($filtros['hora_hasta']) ? $filtros['hora_hasta'] : '23:59:59');
            $query->whereBetween('fecha_hora', [$desde, $hasta]);
        }

        if (!empty($filtros['buscar'])) {
            $query->buscar($filtros['buscar']);
        }

        return $query;
    }

    /**
     * Aplica el orden elegido en el buscador. El nro_expediente se guarda como
     * string (largo variable: 5 a 7 dígitos), por eso se castea a numérico para
     * que el orden sea el real y no el alfabético (donde "999" > "10000").
     */
    public function scopeOrdenadoPor($query, ?string $orden)
    {
        return match ($orden) {
            'expediente_menor_mayor' => $query->orderByRaw('CAST(nro_expediente AS UNSIGNED) ASC'),
            'expediente_mayor_menor' => $query->orderByRaw('CAST(nro_expediente AS UNSIGNED) DESC'),
            'fecha_antigua' => $query->orderBy('fecha_hora', 'asc'),
            default => $query->orderBy('fecha_hora', 'desc'),
        };
    }

    /**
     * @param mixed $tipos
     * @return array<int, string>
     */
    private static function tiposNormalizados($tipos): array
    {
        if (!is_array($tipos)) {
            $tipos = [$tipos];
        }

        return collect($tipos)
            ->filter(fn($tipo) => is_string($tipo) && trim($tipo) !== '')
            ->map(fn($tipo) => trim($tipo))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param array<int, string> $tipos
     */
    private static function aplicarFiltroTipos($query, array $tipos): void
    {
        $query->where(function ($q) use ($tipos) {
            foreach ($tipos as $tipo) {
                if ($tipo === 'Dispositivo Dual') {
                    $q->orWhereRaw("tipo_servicio REGEXP '^[Dd]\\.?[[:space:]]*[Dd]\\.?$'");
                } else {
                    $q->orWhere('tipo_servicio', 'like', '%' . $tipo . '%');
                }
            }
        });
    }
}
