<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class TicketTicketera extends Model
{
    protected $table = 'tickets_ticketera';

    protected $fillable = [
        'incidencia_911_id',
        'codigo_interno',
        'codigo_ticketera',
        'referencia_ticketera',
        'url_seguimiento',
        'asunto',
        'texto_enviado',
        'tipo_equipo',
        'modelo_equipo',
        'movil',
        'recurso_id',
        'equipo_id',
        'tipo_terminal_id',
        'dependencia',
        'oficina',
        'problema_detectado',
        'fecha_inicio_falla',
        'fecha_fin_falla',
        'prioridad',
        'subsistema',
        'camaras_afectadas',
        'cantidad_items',
        'periodo_facturado',
        'aplica_calculo',
        'observaciones',
        'estado_envio',
        'estado_ticketera',
        'enviado_en',
        'ultimo_error',
    ];

    protected $casts = [
        'aplica_calculo'     => 'boolean',
        'enviado_en'         => 'datetime',
        'fecha_inicio_falla' => 'datetime',
        'fecha_fin_falla'    => 'datetime',
        'camaras_afectadas'  => 'array',
    ];

    public function incidencia911(): BelongsTo
    {
        return $this->belongsTo(Incidencia911::class, 'incidencia_911_id');
    }

    public function recurso(): BelongsTo
    {
        return $this->belongsTo(Recurso::class, 'recurso_id');
    }

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class, 'equipo_id');
    }

    public function tipoTerminal(): BelongsTo
    {
        return $this->belongsTo(TipoTerminal::class, 'tipo_terminal_id');
    }

    /**
     * Filtra por grupo de estado de la ticketera: 'nuevos', 'en_progreso'
     * (incluye en espera y respondido) o 'resueltos'. Agrupa las variantes
     * que vienen del Excel/HESK con el mismo criterio que colorEstadoTicketera().
     */
    public function scopeGrupoEstado(Builder $query, string $grupo): Builder
    {
        return match ($grupo) {
            'nuevos'      => $query->where(function (Builder $condiciones): void {
                $condiciones->whereNull('estado_ticketera')
                    ->orWhere('estado_ticketera', 'Nuevo')
                    ->orWhere('estado_ticketera', 'creado');
            }),
            'en_progreso' => $query->where(function (Builder $condiciones): void {
                $condiciones->where('estado_ticketera', 'like', '%progre%')
                    ->orWhere('estado_ticketera', 'like', '%espera%')
                    ->orWhere('estado_ticketera', 'like', '%respond%');
            }),
            'resueltos'   => $query->where(function (Builder $condiciones): void {
                $condiciones->where('estado_ticketera', 'like', '%resuel%')
                    ->orWhere('estado_ticketera', 'like', '%cierre%');
            }),
            default       => $query,
        };
    }

    public function estaEnviado(): bool
    {
        return $this->estado_envio === 'enviado';
    }

    public function yaEstaEnTicketera(): bool
    {
        return !empty($this->codigo_ticketera);
    }

    public function estadoTicketeraLegible(): string
    {
        return $this->estado_ticketera ?: 'Nuevo';
    }

    /**
     * Color de badge Bootstrap según el estado del ticket en la ticketera
     * (Resuelto / En progreso / Nuevo / etc., tal como viene del Excel o HESK).
     */
    public function colorEstadoTicketera(): string
    {
        $estado = mb_strtolower($this->estadoTicketeraLegible());

        return match (true) {
            str_contains($estado, 'resuel') || str_contains($estado, 'cierre') => 'success',
            str_contains($estado, 'progre') || str_contains($estado, 'espera') => 'warning',
            str_contains($estado, 'respond')                                   => 'info',
            default                                                            => 'primary',
        };
    }

    /**
     * Parsea "observaciones" (columna "Respuestas P.G." importada del Excel)
     * en entradas individuales, separando por las marcas de fecha/hora que
     * HESK antepone a cada respuesta ("Y-m-d H:i:s" en su propia línea).
     * Si el texto no tiene ese formato (dato viejo o cargado a mano), se
     * devuelve como una única entrada sin fecha.
     *
     * @return array<int, array{fecha: ?\Illuminate\Support\Carbon, texto: string}>
     */
    public function respuestas(): array
    {
        $texto = trim((string) $this->observaciones);

        if ($texto === '') {
            return [];
        }

        preg_match_all(
            '/^(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\s*\n(.*?)(?=^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\s*\n|\z)/ms',
            $texto,
            $coincidencias,
            PREG_SET_ORDER
        );

        if ($coincidencias === []) {
            return [['fecha' => null, 'texto' => $texto]];
        }

        return array_map(
            fn (array $match): array => [
                'fecha' => \Illuminate\Support\Carbon::createFromFormat('Y-m-d H:i:s', $match[1]),
                'texto' => trim($match[2]),
            ],
            $coincidencias
        );
    }

    /**
     * Combina respuestas obtenidas en vivo de HESK (TicketeraService::obtenerRespuestas)
     * con las ya guardadas en "observaciones", evitando duplicados por fecha, y
     * persiste el resultado con el mismo formato que usa el importador de Excel
     * ("Y-m-d H:i:s" en su propia línea seguido del texto) para que respuestas()
     * lo siga parseando igual.
     *
     * @param array<int, array{autor: ?string, fecha: ?\Illuminate\Support\Carbon, texto: string}> $respuestasHesk
     * @return int cantidad de respuestas nuevas agregadas
     */
    public function fusionarRespuestas(array $respuestasHesk): int
    {
        $existentes = $this->respuestas();
        $fechasExistentes = collect($existentes)
            ->filter(fn (array $r) => $r['fecha'] !== null)
            ->map(fn (array $r) => $r['fecha']->format('Y-m-d H:i:s'))
            ->flip()
            ->all();

        $nuevas = [];
        foreach ($respuestasHesk as $r) {
            if ($r['fecha'] === null) {
                continue;
            }

            $clave = $r['fecha']->format('Y-m-d H:i:s');
            if (isset($fechasExistentes[$clave])) {
                continue;
            }
            $fechasExistentes[$clave] = true;

            $nuevas[] = [
                'fecha' => $r['fecha'],
                'texto' => $r['autor'] ? "{$r['autor']}:\n{$r['texto']}" : $r['texto'],
            ];
        }

        if ($nuevas === []) {
            return 0;
        }

        $todas = array_merge($existentes, $nuevas);
        usort($todas, fn (array $a, array $b) => ($a['fecha']?->timestamp ?? 0) <=> ($b['fecha']?->timestamp ?? 0));

        $this->observaciones = collect($todas)
            ->map(fn (array $r) => $r['fecha'] !== null ? $r['fecha']->format('Y-m-d H:i:s') . "\n" . $r['texto'] : $r['texto'])
            ->implode("\n");
        $this->save();

        return count($nuevas);
    }
}
