<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DevolucionEquipo extends Model
{
    use HasFactory;

    protected $table = 'devoluciones_equipos';

    protected $fillable = [
        'entrega_id',
        'fecha_devolucion',
        'hora_devolucion',
        'personal_devuelve',
        'legajo_devuelve',
        'observaciones',
        'usuario_creador'
    ];

    protected $dates = [
        'fecha_devolucion'
    ];

    // Relación con la entrega
    public function entrega()
    {
        return $this->belongsTo(EntregaEquipo::class, 'entrega_id');
    }

    // Relación con el detalle de devoluciones
    public function detalleDevoluciones()
    {
        return $this->hasMany(DetalleDevolucionEquipo::class, 'devolucion_id');
    }

    // Relación con equipos a través del detalle
    public function equipos()
    {
        return $this->belongsToMany(FlotaGeneral::class, 'detalle_devoluciones_equipos', 'devolucion_id', 'equipo_id');
    }
}
