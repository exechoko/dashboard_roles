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
        'con_2_baterias',
        'observaciones',
        'rutas_imagenes',
        'ruta_archivo',
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

    // Relación con accesorios
    public function accesorios()
    {
        return $this->hasMany(DetalleEntregaAccesorio::class, 'entrega_id');
    }

    // Relación específica para cunas cargadoras
    public function cunasCargadoras()
    {
        return $this->hasMany(DetalleEntregaAccesorio::class, 'entrega_id')
                ->where('tipo_accesorio', DetalleEntregaAccesorio::TIPO_CUNA_CARGADORA);
    }

    // Relación específica para transformadores
    public function transformadores()
    {
        return $this->hasMany(DetalleEntregaAccesorio::class, 'entrega_id')
                ->where('tipo_accesorio', DetalleEntregaAccesorio::TIPO_TRANSFORMADOR);
    }

    // Relación con equipos a través del detalle
    public function equipos()
    {
        return $this->belongsToMany(FlotaGeneral::class, 'detalle_entregas_equipos', 'entrega_id', 'equipo_id');
    }

    // Relación con devoluciones
    public function devoluciones()
    {
        return $this->hasMany(DevolucionEquipo::class, 'entrega_id');
    }

    // Método para obtener equipos pendientes de devolución
    public function equiposPendientes()
    {
        $equiposDevueltos = $this->devoluciones()
            ->with('equipos')
            ->get()
            ->pluck('equipos')
            ->flatten()
            ->pluck('id')
            ->unique();

        return $this->equipos()->whereNotIn('flota_general.id', $equiposDevueltos);
    }

    // Método para obtener equipos ya devueltos
    public function equiposDevueltos()
    {
        return $this->devoluciones()
            ->with('equipos')
            ->get()
            ->pluck('equipos')
            ->flatten()
            ->unique();
    }

    // Método para calcular el estado actual de la entrega
    public function calcularEstado()
    {
        $totalEquipos = $this->equipos->count();
        $equiposDevueltos = $this->equiposDevueltos()->count();

        if ($equiposDevueltos == 0) {
            return 'entregado';
        } elseif ($equiposDevueltos < $totalEquipos) {
            return 'devolucion_parcial';
        } else {
            return 'devuelto';
        }
    }

    // Actualizar estado automáticamente
    public function actualizarEstado()
    {
        $nuevoEstado = $this->calcularEstado();
        $this->update(['estado' => $nuevoEstado]);
        return $nuevoEstado;
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

    /**
     * Scope para buscar por TEI
     */
    public function scopeBuscarPorTei($query, $tei)
    {
        return $query->whereHas('equipos.equipo', function ($q) use ($tei) {
            $q->where('tei', 'LIKE', "%{$tei}%");
        });
    }

    /**
     * Scope para buscar por ISSI
     */
    public function scopeBuscarPorIssi($query, $issi)
    {
        return $query->whereHas('equipos.equipo', function ($q) use ($issi) {
            $q->where('issi', 'LIKE', "%{$issi}%");
        });
    }

    /**
     * Scope para buscar por fecha
     */
    public function scopeBuscarPorFecha($query, $fecha)
    {
        return $query->whereDate('fecha_entrega', $fecha);
    }

    /**
     * Scope para buscar por dependencia
     */
    public function scopeBuscarPorDependencia($query, $dependencia)
    {
        return $query->where('dependencia', 'LIKE', "%{$dependencia}%");
    }

    /**
     * Scope para buscar por número de acta (ID)
     */
    public function scopeBuscarPorNumeroActa($query, $numeroActa)
    {
        return $query->where('id', $numeroActa);
    }
}
