<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RecursoBitacora extends Model
{
    use HasFactory;

    public const ESTADO_ABIERTO = 'abierto';
    public const ESTADO_CERRADO = 'cerrado';

    /** Categorías de una entrada de bitácora. clave => etiqueta. */
    public const CATEGORIAS = [
        'mantenimiento'    => 'Mantenimiento',
        'chapa_pintura'    => 'Chapa y pintura',
        'service'          => 'Service',
        'gomeria'          => 'Gomería',
        'electrica'        => 'Eléctrica',
        'dano_siniestro'   => 'Daño / siniestro',
        'documentacion'    => 'Documentación',
        'observacion'      => 'Observación',
        'otro'             => 'Otro',
    ];

    /** Categorías que sugieren poner el recurso "En taller" al abrir la entrada. */
    public const CATEGORIAS_TALLER = ['mantenimiento', 'chapa_pintura', 'service', 'electrica'];

    protected $table = 'recurso_bitacora';

    protected $fillable = [
        'recurso_id',
        'fecha_hora',
        'categoria',
        'descripcion',
        'estado',
        'km',
        'taller',
        'costo',
        'cerrada_en',
        'cerrada_por',
        'user_id',
    ];

    protected $casts = [
        'fecha_hora'  => 'datetime',
        'cerrada_en'  => 'datetime',
        'km'          => 'integer',
        'costo'       => 'decimal:2',
    ];

    public function recurso(): BelongsTo
    {
        return $this->belongsTo(Recurso::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function cerradaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cerrada_por');
    }

    public function adjuntos(): HasMany
    {
        return $this->hasMany(RecursoBitacoraAdjunto::class, 'bitacora_id');
    }

    public function seguimientos(): HasMany
    {
        return $this->hasMany(RecursoBitacoraSeguimiento::class, 'bitacora_id')->orderBy('created_at');
    }

    public function solicitudes(): HasMany
    {
        return $this->hasMany(RecursoBitacoraSolicitud::class, 'bitacora_id')->latest();
    }

    public function solicitudPendiente(): HasOne
    {
        return $this->hasOne(RecursoBitacoraSolicitud::class, 'bitacora_id')
            ->where('estado', RecursoBitacoraSolicitud::ESTADO_PENDIENTE);
    }

    public function scopeAbiertas(Builder $query): Builder
    {
        return $query->where('estado', self::ESTADO_ABIERTO);
    }

    public function scopeOrdenadas(Builder $query): Builder
    {
        return $query->orderByDesc('fecha_hora')->orderByDesc('id');
    }

    public function estaAbierta(): bool
    {
        return $this->estado === self::ESTADO_ABIERTO;
    }

    public function categoriaLabel(): string
    {
        return self::CATEGORIAS[$this->categoria] ?? $this->categoria;
    }
}
