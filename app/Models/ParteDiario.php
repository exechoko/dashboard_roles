<?php

namespace App\Models;

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
}
