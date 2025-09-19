<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bodycam extends Model
{
    use HasFactory;

    protected $table = 'bodycams';

    protected $fillable = [
        'codigo',
        'imei',
        'numero_serie',
        'marca',
        'modelo',
        'numero_tarjeta_sd',
        'numero_bateria',
        'estado',
        'fecha_adquisicion',
        'observaciones',
        'usuario_creador'
    ];

    protected $casts = [
        'fecha_adquisicion' => 'date'
    ];

    // Estados posibles
    const ESTADO_DISPONIBLE = 'disponible';
    const ESTADO_ENTREGADA = 'entregada';
    const ESTADO_PERDIDA = 'perdida';
    const ESTADO_MANTENIMIENTO = 'mantenimiento';
    const ESTADO_DADA_BAJA = 'dada_baja';

    // Relaciones
    public function entregasActuales()
    {
        return $this->belongsToMany(EntregaBodycam::class, 'detalle_entrega_bodycams', 'bodycam_id', 'entrega_id')
            ->whereIn('entregas_bodycams.estado', ['entregada', 'parcialmente_devuelta']);
    }

    public function entregasHistoricas()
    {
        return $this->belongsToMany(EntregaBodycam::class, 'detalle_entrega_bodycams', 'bodycam_id', 'entrega_id');
    }

    public function detalleEntregas()
    {
        return $this->hasMany(DetalleEntregaBodycam::class, 'bodycam_id');
    }

    public function detalleDevoluciones()
    {
        return $this->hasMany(DetalleDevolucionBodycam::class, 'bodycam_id');
    }

    // Scopes
    public function scopeDisponibles($query)
    {
        return $query->where('estado', self::ESTADO_DISPONIBLE);
    }

    public function scopeEntregadas($query)
    {
        return $query->where('estado', self::ESTADO_ENTREGADA);
    }

    public function scopePorCodigo($query, $codigo)
    {
        return $query->where('codigo', 'like', '%' . $codigo . '%');
    }

    public function scopePorSerie($query, $serie)
    {
        return $query->where('numero_serie', 'like', '%' . $serie . '%');
    }

    // Métodos
    public function estaDisponible()
    {
        return $this->estado === self::ESTADO_DISPONIBLE;
    }

    public function estaEntregada()
    {
        return $this->estado === self::ESTADO_ENTREGADA;
    }

    public function obtenerEntregaActual()
    {
        return $this->entregasActuales()->first();
    }

    public function marcarComoEntregada()
    {
        $this->update(['estado' => self::ESTADO_ENTREGADA]);
    }

    public function marcarComoDisponible()
    {
        $this->update(['estado' => self::ESTADO_DISPONIBLE]);
    }

    public function marcarComoPerdida()
    {
        $this->update(['estado' => self::ESTADO_PERDIDA]);
    }

    // Mutators & Accessors
    public function getEstadoFormateadoAttribute()
    {
        $estados = [
            self::ESTADO_DISPONIBLE => 'Disponible',
            self::ESTADO_ENTREGADA => 'Entregada',
            self::ESTADO_PERDIDA => 'Perdida',
            self::ESTADO_MANTENIMIENTO => 'En Mantenimiento',
            self::ESTADO_DADA_BAJA => 'Dada de Baja'
        ];

        return $estados[$this->estado] ?? 'Desconocido';
    }

    public function getCodigoCompletoAttribute()
    {
        return $this->codigo . ' - ' . $this->numero_serie;
    }
}
