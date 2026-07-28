<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class PersonalLicencia extends Model
{
    protected $table = 'personal_licencias';

    protected $fillable = [
        'personal_id',
        'personal911_licencia_id',
        'personal911_funcionario_id',
        'tipo_licencia_id',
        'tipo_licencia',
        'motivo',
        'fecha_inicio',
        'cantidad_dias',
        'fecha_fin',
    ];

    protected $casts = [
        'personal911_licencia_id' => 'integer',
        'personal911_funcionario_id' => 'integer',
        'tipo_licencia_id' => 'integer',
        'cantidad_dias' => 'integer',
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
    ];

    public function personal(): BelongsTo
    {
        return $this->belongsTo(Personal::class);
    }

    public function scopeVigentes(Builder $query, ?CarbonInterface $fecha = null): Builder
    {
        $fecha = Carbon::parse(($fecha ?? Carbon::today())->toDateString());

        return $query
            ->whereDate('fecha_inicio', '<=', $fecha->toDateString())
            ->whereDate('fecha_fin', '>=', $fecha->toDateString());
    }

    public function estaVigente(?CarbonInterface $fecha = null): bool
    {
        if ($this->fecha_inicio === null || $this->fecha_fin === null) {
            return false;
        }

        $fecha = Carbon::parse(($fecha ?? Carbon::today())->toDateString());

        return !$fecha->lt($this->fecha_inicio) && !$fecha->gt($this->fecha_fin);
    }

    public function getDiasTranscurridosAttribute(): int
    {
        if (!$this->estaVigente()) {
            return 0;
        }

        $hoy = Carbon::today();
        $fechaFin = $this->fecha_fin->lt($hoy) ? $this->fecha_fin : $hoy;

        return $this->fecha_inicio->diffInDays($fechaFin) + 1;
    }

    /**
     * Agrupa licencias consecutivas u ocupadas por un mismo funcionario.
     * Una fecha de inicio al día siguiente del fin anterior mantiene la continuidad.
     *
     * @return array{licencias: Collection<int, self>, tipos: Collection<int, string>, fecha_inicio: Carbon, fecha_fin: Carbon, dias_otorgados: int, dias_transcurridos: int}|null
     */
    public static function resumenContinuidad(Collection $licencias, ?CarbonInterface $fecha = null): ?array
    {
        $fecha = Carbon::parse(($fecha ?? Carbon::today())->toDateString());
        $registros = $licencias
            ->filter(fn (self $licencia): bool => $licencia->fecha_inicio !== null && $licencia->fecha_fin !== null)
            ->sortBy(fn (self $licencia): string => $licencia->fecha_inicio->toDateString())
            ->values();

        $cadenas = [];

        foreach ($registros as $licencia) {
            $fechaInicio = $licencia->fecha_inicio->copy()->startOfDay();
            $fechaFin = $licencia->fecha_fin->copy()->startOfDay();

            if ($fechaFin->lt($fechaInicio)) {
                continue;
            }

            $indice = count($cadenas) - 1;
            if ($indice < 0 || $fechaInicio->gt($cadenas[$indice]['fecha_fin']->copy()->addDay())) {
                $cadenas[] = [
                    'licencias' => collect([$licencia]),
                    'fecha_inicio' => $fechaInicio,
                    'fecha_fin' => $fechaFin,
                    'dias_otorgados' => max(0, (int) $licencia->cantidad_dias),
                ];

                continue;
            }

            $cadenas[$indice]['licencias']->push($licencia);
            $cadenas[$indice]['fecha_fin'] = $fechaFin->gt($cadenas[$indice]['fecha_fin'])
                ? $fechaFin
                : $cadenas[$indice]['fecha_fin'];
            $cadenas[$indice]['dias_otorgados'] += max(0, (int) $licencia->cantidad_dias);
        }

        $cadenaActual = collect($cadenas)->first(
            fn (array $cadena): bool => !$fecha->lt($cadena['fecha_inicio']) && !$fecha->gt($cadena['fecha_fin'])
        );

        if ($cadenaActual === null) {
            return null;
        }

        $tipos = $cadenaActual['licencias']
            ->map(fn (self $licencia): string => trim((string) $licencia->tipo_licencia))
            ->filter(fn (string $tipo): bool => $tipo !== '')
            ->unique()
            ->values();

        $fechaCorte = $cadenaActual['fecha_fin']->lt($fecha) ? $cadenaActual['fecha_fin'] : $fecha;

        return [
            'licencias' => $cadenaActual['licencias']->values(),
            'tipos' => $tipos,
            'fecha_inicio' => $cadenaActual['fecha_inicio'],
            'fecha_fin' => $cadenaActual['fecha_fin'],
            'dias_otorgados' => $cadenaActual['dias_otorgados'],
            'dias_transcurridos' => $cadenaActual['fecha_inicio']->diffInDays($fechaCorte) + 1,
        ];
    }
}
