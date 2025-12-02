<?php
// app/Models/PatrimonioBien.php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PatrimonioBien extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'patrimonio_bienes';

    protected $fillable = [
        'tipo_bien_id',
        'destino_id',
        'ubicacion',
        'siaf',
        'descripcion',
        'numero_serie',
        'estado',
        'fecha_alta',
        'tabla_origen',
        'id_origen',
        'observaciones',
    ];

    protected $casts = [
        'fecha_alta' => 'date',
    ];

    const ESTADOS = [
        'activo' => 'Activo',
        'baja' => 'Baja',
        'transferido' => 'Transferido',
        'desuso' => 'En Desuso',
        'rotura' => 'Rotura',
    ];

    /**
     * Relación con tipo de bien
     */
    public function tipoBien()
    {
        return $this->belongsTo(PatrimonioTipoBien::class, 'tipo_bien_id');
    }

    /**
     * Relación con destino
     */
    public function destino()
    {
        return $this->belongsTo(Destino::class, 'destino_id');
    }

    /**
     * Relación con movimientos
     */
    public function movimientos()
    {
        return $this->hasMany(PatrimonioMovimiento::class, 'bien_id');
    }

    /**
     * Obtener último movimiento
     */
    public function ultimoMovimiento()
    {
        return $this->hasOne(PatrimonioMovimiento::class, 'bien_id')->latestOfMany('fecha');
    }

    /**
     * Scope para bienes activos
     */
    public function scopeActivos($query)
    {
        return $query->where('estado', 'activo');
    }

    /**
     * Scope para bienes por tipo
     */
    public function scopePorTipo($query, $tipoId)
    {
        return $query->where('tipo_bien_id', $tipoId);
    }

    /**
     * Scope para bienes por destino
     */
    public function scopePorDestino($query, $destinoId)
    {
        return $query->where('destino_id', $destinoId);
    }

    /**
     * Obtener el estado formateado
     */
    public function getEstadoFormateadoAttribute()
    {
        return self::ESTADOS[$this->estado] ?? $this->estado;
    }

    /**
     * Obtener el modelo origen si existe
     */
    public function getModeloOrigenAttribute()
    {
        if (!$this->tabla_origen || !$this->id_origen) {
            return null;
        }

        try {
            $modelClass = 'App\\Models\\' . str_replace('_', '', ucwords($this->tabla_origen, '_'));
            if (class_exists($modelClass)) {
                return $modelClass::find($this->id_origen);
            }
        } catch (Exception $e) {
            return null;
        }

        return null;
    }

    /**
     * Registrar un movimiento
     */
    public function registrarMovimiento($tipo, $destinoDesde = null, $ubicacionDesde = null, $destinoHasta = null, $ubicacionHasta = null, $observaciones = null)
    {
        return $this->movimientos()->create([
            'tipo_movimiento' => $tipo,
            'destino_desde_id' => $destinoDesde,
            'ubicacion_desde' => $ubicacionDesde,
            'destino_hasta_id' => $destinoHasta,
            'ubicacion_hasta' => $ubicacionHasta,
            'fecha' => now(),
            'observaciones' => $observaciones,
            'usuario_creador' => auth()->user()->name ?? 'Sistema',
        ]);
    }
}
