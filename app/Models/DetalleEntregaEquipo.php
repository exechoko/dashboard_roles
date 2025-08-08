<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetalleEntregaEquipo extends Model
{
    use HasFactory;
    protected $table = 'detalle_entregas_equipos';

    protected $fillable = [
        'entrega_id',
        'equipo_id'
    ];

    // Relación con la entrega principal
    public function entrega()
    {
        return $this->belongsTo(EntregaEquipo::class, 'entrega_id');
    }

    // Relación con el equipo
    public function equipo()
    {
        return $this->belongsTo(FlotaGeneral::class, 'equipo_id');
    }
}
