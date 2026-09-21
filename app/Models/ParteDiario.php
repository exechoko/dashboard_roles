<?php

namespace App\Models;

use App\Services\ParteDiarioBorradorService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ParteDiario extends Model
{
    use HasFactory;

    public const TIPO_MOVILES = 'moviles';
    public const TIPO_MOTOS = 'motos';

    protected $table = 'partes_diarios';

    protected $fillable = [
        'destino_id',
        'tipo',
        'fecha',
        'guardia',
        'horario',
        'fecha_inicio',
        'fecha_fin',
        'guardia_interna',
        'licencia_ordinaria',
        'novedades_pie',
        'user_id',
    ];

    protected $casts = [
        'fecha'        => 'date',
        'fecha_inicio' => 'datetime',
        'fecha_fin'    => 'datetime',
    ];

    public function seccion(): BelongsTo
    {
        return $this->belongsTo(Destino::class, 'destino_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function estadosDiarios(): HasMany
    {
        return $this->hasMany(RecursoEstadoDiario::class);
    }

    public function dotaciones(): HasMany
    {
        return $this->hasMany(RecursoDotacion::class);
    }

    public function asignaciones(): HasMany
    {
        return $this->hasMany(ParteDiarioAsignacion::class)->orderBy('orden');
    }

    public function esMotos(): bool
    {
        return $this->tipo === self::TIPO_MOTOS;
    }

    /**
     * La hoja NOVEDADES de la División para este parte. Se resuelve por
     * fecha + guardia (es única a ese nivel, no por sección), así que no
     * puede ser una relación eager-loadeable.
     */
    public function novedades(): ?ParteDiarioNovedades
    {
        return ParteDiarioNovedades::firstWhere([
            'fecha'   => $this->fecha->toDateString(),
            'guardia' => $this->guardia,
        ]);
    }

    /**
     * El parte de MÓVILES de la misma división/turno (mismo fecha_inicio),
     * exista o no todavía. Los rubros de NOVEDADES sobre el estado de los
     * móviles (QAP, fuera de servicio, etc.) siempre salen de ahí, sin
     * importar si $this es el parte de móviles o el de motos.
     */
    public function parteMoviles(): ?self
    {
        if ($this->tipo === self::TIPO_MOVILES) {
            return $this;
        }

        return self::query()
            ->where('destino_id', $this->destino_id)
            ->where('tipo', self::TIPO_MOVILES)
            ->where('fecha_inicio', $this->fecha_inicio)
            ->first();
    }

    /**
     * Los 14 rubros de la hoja NOVEDADES de la División, mezclando el texto
     * manual (personal, moviles_prever) con los rubros de móviles calculados
     * a partir de los estado_dia del turno (ver ParteDiarioNovedades::RUBROS_ESTADO_RECURSO).
     *
     * @return array<string, array{etiqueta: string, valor: string}>
     */
    public function rubrosNovedades(?ParteDiarioNovedades $novedades = null): array
    {
        $estados = $this->parteMoviles()?->estadosDiarios()->with('recurso')->get() ?? collect();
        $computados = ParteDiarioBorradorService::novedadesDesdeEstados($estados);

        return ($novedades ?? $this->novedades() ?? new ParteDiarioNovedades())->rubrosCompletos($computados);
    }

    public function guardiaLabel(): string
    {
        return RecursoEstadoDiario::$guardias[$this->guardia] ?? $this->guardia;
    }

    public function horarioLabel(): string
    {
        return RecursoEstadoDiario::$horarios[$this->horario] ?? $this->horario;
    }
}
