<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecursoTransferencia extends Model
{
    use HasFactory;

    public const ESTADO_PENDIENTE = 'pendiente';
    public const ESTADO_CONFIRMADA = 'confirmada';
    public const ESTADO_RECHAZADA = 'rechazada';

    protected $table = 'recurso_transferencias';

    protected $fillable = [
        'recurso_id',
        'vehiculo_id',
        'destino_transferencia_id',
        'reparticion_texto',
        'fecha_transferencia',
        'observaciones',
        'estado',
        'user_id_reporte',
        'user_id_resolucion',
        'fecha_resolucion',
        'motivo_rechazo',
    ];

    protected $casts = [
        'fecha_transferencia' => 'date',
        'fecha_resolucion'    => 'datetime',
    ];

    public function recurso(): BelongsTo
    {
        return $this->belongsTo(Recurso::class);
    }

    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class);
    }

    public function destinoTransferencia(): BelongsTo
    {
        return $this->belongsTo(Destino::class, 'destino_transferencia_id');
    }

    public function usuarioReporte(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id_reporte');
    }

    public function usuarioResolucion(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id_resolucion');
    }

    public function scopePendientes($query)
    {
        return $query->where('estado', self::ESTADO_PENDIENTE);
    }

    public function scopeConfirmadas($query)
    {
        return $query->where('estado', self::ESTADO_CONFIRMADA);
    }

    public function scopeResueltas($query)
    {
        return $query->whereIn('estado', [self::ESTADO_CONFIRMADA, self::ESTADO_RECHAZADA]);
    }

    public function estaPendiente(): bool
    {
        return $this->estado === self::ESTADO_PENDIENTE;
    }

    public function reparticionDestinoNombre(): string
    {
        return $this->destinoTransferencia?->nombre
            ?? ($this->reparticion_texto ?: 'Desconocida');
    }
}
