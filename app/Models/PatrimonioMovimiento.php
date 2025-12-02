<?php
// app/Models/PatrimonioMovimiento.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatrimonioMovimiento extends Model
{
    use HasFactory;

    protected $table = 'patrimonio_movimientos';

    protected $fillable = [
        'bien_id',
        'tipo_movimiento',
        'destino_desde_id',
        'ubicacion_desde',
        'destino_hasta_id',
        'ubicacion_hasta',
        'fecha',
        'observaciones',
        'usuario_creador',
    ];

    protected $casts = [
        'fecha' => 'datetime',
    ];

    const TIPOS_MOVIMIENTO = [
        'alta' => 'Alta',
        'traslado' => 'Traslado',
        'baja_desuso' => 'Baja por Desuso',
        'baja_transferencia' => 'Baja por Transferencia',
        'baja_rotura' => 'Baja por Rotura',
    ];

    /**
     * Relación con bien
     */
    public function bien()
    {
        return $this->belongsTo(PatrimonioBien::class, 'bien_id');
    }

    /**
     * Relación con destino desde
     */
    public function destinoDesde()
    {
        return $this->belongsTo(Destino::class, 'destino_desde_id');
    }

    /**
     * Relación con destino hasta
     */
    public function destinoHasta()
    {
        return $this->belongsTo(Destino::class, 'destino_hasta_id');
    }

    /**
     * Obtener el tipo formateado
     */
    public function getTipoFormateadoAttribute()
    {
        return self::TIPOS_MOVIMIENTO[$this->tipo_movimiento] ?? $this->tipo_movimiento;
    }

    /**
     * Scope para altas
     */
    public function scopeAltas($query)
    {
        return $query->where('tipo_movimiento', 'alta');
    }

    /**
     * Scope para bajas
     */
    public function scopeBajas($query)
    {
        return $query->whereIn('tipo_movimiento', ['baja_desuso', 'baja_transferencia', 'baja_rotura']);
    }

    /**
     * Scope para traslados
     */
    public function scopeTraslados($query)
    {
        return $query->where('tipo_movimiento', 'traslado');
    }
}
