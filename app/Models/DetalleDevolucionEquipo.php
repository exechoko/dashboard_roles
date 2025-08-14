<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetalleDevolucionEquipo extends Model
{
    use HasFactory;

    protected $table = 'detalle_devoluciones_equipos';

    protected $fillable = [
        'devolucion_id',
        'equipo_id'
    ];

    // Relación con la devolución
    public function devolucion()
    {
        return $this->belongsTo(DevolucionEquipo::class, 'devolucion_id');
    }

    // Relación con el equipo
    public function equipo()
    {
        return $this->belongsTo(FlotaGeneral::class, 'equipo_id');
    }
}
