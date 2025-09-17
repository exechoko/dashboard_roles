<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetalleDevolucionBodycam extends Model
{
    use HasFactory;

    protected $table = 'detalle_devolucion_bodycams';

    protected $fillable = [
        'devolucion_id',
        'bodycam_id',
        'estado_devolucion',
        'observaciones'
    ];

    // Estados de devolución
    const ESTADO_BUENO = 'bueno';
    const ESTADO_DAÑADO = 'dañado';
    const ESTADO_PERDIDO = 'perdido';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->estado_devolucion)) {
                $model->estado_devolucion = self::ESTADO_BUENO;
            }
        });

        static::created(function ($model) {
            // Actualizar estado de la bodycam según el estado de devolución
            $bodycam = $model->bodycam;
            if ($bodycam) {
                switch ($model->estado_devolucion) {
                    case self::ESTADO_BUENO:
                        $bodycam->marcarComoDisponible();
                        break;
                    case self::ESTADO_DAÑADO:
                        $bodycam->update(['estado' => Bodycam::ESTADO_MANTENIMIENTO]);
                        break;
                    case self::ESTADO_PERDIDO:
                        $bodycam->marcarComoPerdida();
                        break;
                }
            }
        });
    }

    // Relaciones
    public function devolucion()
    {
        return $this->belongsTo(DevolucionBodycam::class, 'devolucion_id');
    }

    public function bodycam()
    {
        return $this->belongsTo(Bodycam::class, 'bodycam_id');
    }

    // Accessors
    public function getEstadoDevolucionFormateadoAttribute()
    {
        $estados = [
            self::ESTADO_BUENO => 'Buen Estado',
            self::ESTADO_DAÑADO => 'Dañado',
            self::ESTADO_PERDIDO => 'Perdido'
        ];

        return $estados[$this->estado_devolucion] ?? 'Desconocido';
    }
}
