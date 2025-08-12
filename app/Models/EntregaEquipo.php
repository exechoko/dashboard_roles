<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EntregaEquipo extends Model
{
    use HasFactory;
    protected $table = 'entregas_equipos';

    protected $fillable = [
        'fecha_entrega',
        'hora_entrega',
        'dependencia',
        'personal_receptor',
        'legajo_receptor',
        'personal_entrega',
        'legajo_entrega',
        'motivo_operativo',
        'estado',
        'observaciones',
        'usuario_creador'
    ];

    protected $dates = [
        'fecha_entrega'
    ];

    // Relación con el detalle de entregas
    public function detalleEntregas()
    {
        return $this->hasMany(DetalleEntregaEquipo::class, 'entrega_id');
    }

    // Relación con equipos a través del detalle
    public function equipos()
    {
        return $this->belongsToMany(FlotaGeneral::class, 'detalle_entregas_equipos', 'entrega_id', 'equipo_id');
    }

    // Generar número de acta automático
    public static function generarNumeroActa()
    {
        $year = date('Y');
        $lastActa = static::where('numero_acta', 'like', $year . '-%')->orderBy('numero_acta', 'desc')->first();

        if ($lastActa) {
            $lastNumber = intval(substr($lastActa->numero_acta, -4));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $year . '-' . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    // Scope para búsquedas
    public function scopeBuscarPorTei($query, $tei)
    {
        return $query->whereHas('equipos', function($q) use ($tei) {
            $q->where('tei', 'LIKE', "%{$tei}%");
        });
    }

    public function scopeBuscarPorIssi($query, $issi)
    {
        return $query->whereHas('equipos', function($q) use ($issi) {
            $q->where('issi', 'LIKE', "%{$issi}%");
        });
    }

    public function scopeBuscarPorFecha($query, $fecha)
    {
        return $query->whereDate('fecha_entrega', $fecha);
    }

    public function scopeBuscarPorDependencia($query, $dependencia)
    {
        return $query->where('dependencia', 'LIKE', "%{$dependencia}%");
    }
}
