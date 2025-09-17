<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class DevolucionBodycam extends Model
{
    use HasFactory;

    protected $table = 'devoluciones_bodycams';

    protected $fillable = [
        'entrega_id',
        'fecha_devolucion',
        'hora_devolucion',
        'personal_devuelve',
        'legajo_devuelve',
        'observaciones',
        'rutas_imagenes',
        'usuario_creador'
    ];

    protected $casts = [
        'fecha_devolucion' => 'date',
        'rutas_imagenes' => 'array'
    ];

    // Relaciones
    public function entrega()
    {
        return $this->belongsTo(EntregaBodycam::class, 'entrega_id');
    }

    public function bodycams()
    {
        return $this->belongsToMany(Bodycam::class, 'detalle_devolucion_bodycams', 'devolucion_id', 'bodycam_id');
    }

    public function detalleDevoluciones()
    {
        return $this->hasMany(DetalleDevolucionBodycam::class, 'devolucion_id');
    }

    // Accessors
    public function getFechaDevolucionFormateadaAttribute()
    {
        return $this->fecha_devolucion->format('d/m/Y');
    }

    public function getHoraDevolucionFormateadaAttribute()
    {
        return Carbon::parse($this->hora_devolucion)->format('H:i');
    }

    public function getCantidadBodycamsAttribute()
    {
        return $this->bodycams->count();
    }

    public function getRutasImagenesArrayAttribute()
    {
        return is_string($this->rutas_imagenes)
            ? json_decode($this->rutas_imagenes, true) ?? []
            : ($this->rutas_imagenes ?? []);
    }
}
