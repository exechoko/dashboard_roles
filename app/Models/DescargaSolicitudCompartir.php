<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DescargaSolicitudCompartir extends Model
{
    use HasFactory;

    protected $table = 'descarga_solicitudes_compartir';

    protected $fillable = [
        'archivo_id',
        'usuario_solicita_id',
        'usuario_destino_id',
        'motivo',
        'estado',
        'aprobado_por',
        'motivo_respuesta',
        'respondido_at',
    ];

    protected $casts = [
        'respondido_at' => 'datetime',
    ];

    /**
     * Relación con el archivo
     */
    public function archivo(): BelongsTo
    {
        return $this->belongsTo(DescargaArchivo::class, 'archivo_id');
    }

    /**
     * Relación con el usuario que solicita
     */
    public function usuarioSolicita(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_solicita_id');
    }

    /**
     * Relación con el usuario destino
     */
    public function usuarioDestino(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_destino_id');
    }

    /**
     * Relación con el usuario que aprobó/rechazó
     */
    public function aprobadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aprobado_por');
    }

    /**
     * Scope para solicitudes pendientes
     */
    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    /**
     * Scope para solicitudes aprobadas
     */
    public function scopeAprobadas($query)
    {
        return $query->where('estado', 'aprobado');
    }

    /**
     * Scope para solicitudes rechazadas
     */
    public function scopeRechazadas($query)
    {
        return $query->where('estado', 'rechazado');
    }

    /**
     * Verificar si la solicitud está pendiente
     */
    public function estaPendiente(): bool
    {
        return $this->estado === 'pendiente';
    }

    /**
     * Verificar si la solicitud fue aprobada
     */
    public function fueAprobada(): bool
    {
        return $this->estado === 'aprobado';
    }

    /**
     * Verificar si la solicitud fue rechazada
     */
    public function fueRechazada(): bool
    {
        return $this->estado === 'rechazado';
    }
}
