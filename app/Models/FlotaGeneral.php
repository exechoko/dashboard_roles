<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FlotaGeneral extends Model
{
    protected $table = 'flota_general';
    protected $fillable = [
        'equipo_id',
        'recurso_id',
        'destino_id',
        'fecha_asignacion',
        'fecha_desasignacion',
        'ticket_per',
        'observaciones'
    ];

    public function equipo()
    {
        return $this->belongsTo(Equipo::class);
    }

    public function recurso()
    {
        return $this->belongsTo(Recurso::class);
    }

    public function destino()
    {
        return $this->belongsTo(Destino::class);
    }

    public function historico()
    {
        return $this->belongsTo(Historico::class);
    }

    public function ultimoLugar()
    {
        $hist = Historico::where('equipo_id', $this->equipo_id)->orderBy('created_at', 'desc')->first();
        if (!is_null($hist)) {
            return $hist->destino->nombre . ' - ' . $hist->destino->dependeDe();
        }
        return null;
    }

    public function ultimoMovimiento()
    {
        $hist = Historico::where('equipo_id', $this->equipo_id)->orderBy('fecha_asignacion', 'desc')->first();
        return $hist ? $hist : null;
    }

    public function auditoria()
    {
        return $this->hasMany(Auditoria::class);
    }

    public function entregasActivas()
    {
        return $this->belongsToMany(EntregaEquipo::class, 'detalle_entregas_equipos', 'equipo_id', 'entrega_id')
            ->whereIn('estado', ['entregado', 'devolucion_parcial'])
            ->whereDoesntHave('devoluciones.detalleDevoluciones', function ($q) {
                $q->whereColumn('detalle_devoluciones_equipos.equipo_id', 'detalle_entregas_equipos.equipo_id');
            });
    }
}
