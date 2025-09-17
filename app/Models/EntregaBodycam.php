<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class EntregaBodycam extends Model
{
    use HasFactory;

    protected $table = 'entregas_bodycams';

    protected $fillable = [
        'fecha_entrega',
        'hora_entrega',
        'dependencia',
        'personal_receptor',
        'legajo_receptor',
        'personal_entrega',
        'legajo_entrega',
        'motivo_operativo',
        'observaciones',
        'rutas_imagenes',
        'ruta_archivo',
        'estado',
        'usuario_creador'
    ];

    protected $casts = [
        'fecha_entrega' => 'date',
        'rutas_imagenes' => 'array'
    ];

    // Estados posibles
    const ESTADO_ENTREGADA = 'entregada';
    const ESTADO_PARCIALMENTE_DEVUELTA = 'parcialmente_devuelta';
    const ESTADO_DEVUELTA = 'devuelta';
    const ESTADO_PERDIDA = 'perdida';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->estado)) {
                $model->estado = self::ESTADO_ENTREGADA;
            }
        });
    }

    // Relaciones
    public function bodycams()
    {
        return $this->belongsToMany(Bodycam::class, 'detalle_entrega_bodycams', 'entrega_id', 'bodycam_id');
    }

    public function detalleEntregas()
    {
        return $this->hasMany(DetalleEntregaBodycam::class, 'entrega_id');
    }

    public function devoluciones()
    {
        return $this->hasMany(DevolucionBodycam::class, 'entrega_id');
    }

    // Scopes
    public function scopeBuscarPorCodigo($query, $codigo)
    {
        return $query->whereHas('bodycams', function ($q) use ($codigo) {
            $q->where('codigo', 'like', '%' . $codigo . '%');
        });
    }

    public function scopeBuscarPorSerie($query, $serie)
    {
        return $query->whereHas('bodycams', function ($q) use ($serie) {
            $q->where('numero_serie', 'like', '%' . $serie . '%');
        });
    }

    public function scopeBuscarPorFecha($query, $fecha)
    {
        return $query->whereDate('fecha_entrega', $fecha);
    }

    public function scopeBuscarPorDependencia($query, $dependencia)
    {
        return $query->where('dependencia', 'like', '%' . $dependencia . '%');
    }

    public function scopeActivas($query)
    {
        return $query->whereIn('estado', [self::ESTADO_ENTREGADA, self::ESTADO_PARCIALMENTE_DEVUELTA]);
    }

    public function scopeDevueltas($query)
    {
        return $query->where('estado', self::ESTADO_DEVUELTA);
    }

    // Métodos
    public function bodycamsPendientes()
    {
        $bodycamsDevueltas = $this->devoluciones()
            ->with('bodycams')
            ->get()
            ->pluck('bodycams')
            ->flatten()
            ->pluck('id')
            ->unique()
            ->toArray();

        return $this->bodycams()->whereNotIn('bodycams.id', $bodycamsDevueltas);
    }

    public function bodycamsDevueltas()
    {
        return $this->devoluciones()
            ->with('bodycams')
            ->get()
            ->pluck('bodycams')
            ->flatten()
            ->unique('id');
    }

    public function actualizarEstado()
    {
        $totalBodycams = $this->bodycams->count();
        $bodycamsDevueltas = $this->bodycamsDevueltas()->count();

        if ($bodycamsDevueltas === 0) {
            $this->estado = self::ESTADO_ENTREGADA;
        } elseif ($bodycamsDevueltas < $totalBodycams) {
            $this->estado = self::ESTADO_PARCIALMENTE_DEVUELTA;
        } else {
            $this->estado = self::ESTADO_DEVUELTA;
        }

        $this->save();
    }

    public function estaCompletamenteDevuelta()
    {
        return $this->estado === self::ESTADO_DEVUELTA;
    }

    public function tieneDevoluciones()
    {
        return $this->devoluciones()->exists();
    }

    // Accessors
    public function getEstadoFormateadoAttribute()
    {
        $estados = [
            self::ESTADO_ENTREGADA => 'Entregada',
            self::ESTADO_PARCIALMENTE_DEVUELTA => 'Parcialmente Devuelta',
            self::ESTADO_DEVUELTA => 'Devuelta',
            self::ESTADO_PERDIDA => 'Perdida'
        ];

        return $estados[$this->estado] ?? 'Desconocido';
    }

    public function getFechaEntregaFormateadaAttribute()
    {
        return $this->fecha_entrega->format('d/m/Y');
    }

    public function getHoraEntregaFormateadaAttribute()
    {
        return Carbon::parse($this->hora_entrega)->format('H:i');
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
