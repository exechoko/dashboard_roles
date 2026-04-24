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
        'observaciones',
        'patrimoniado',
        'destino_patrimonial_id',
        'cargo_id',
        'fecha_patrimonio',
    ];

    protected $casts = [
        'patrimoniado' => 'boolean',
        'fecha_patrimonio' => 'date',
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

    // ─── Relaciones patrimoniales ────────────────────────────

    public function cargo()
    {
        return $this->belongsTo(PatrimonioCargo::class, 'cargo_id');
    }

    public function destinoPatrimonial()
    {
        return $this->belongsTo(Destino::class, 'destino_patrimonial_id');
    }

    // ─── Scopes patrimoniales ────────────────────────────────

    public function scopePatrimoniados($query)
    {
        return $query->where('patrimoniado', true);
    }

    public function scopeSinPatrimoniar($query)
    {
        return $query->where('patrimoniado', false);
    }

    public function scopePendientesFirma($query)
    {
        return $query->where('patrimoniado', true)
            ->whereHas('cargo', function ($q) {
                $q->where('estado', 'pendiente');
            });
    }

    // ─── Métodos patrimoniales ───────────────────────────────

    /**
     * Marcar el equipo como patrimoniado
     */
    public function patrimoniar($destinoId, $cargoId, $fecha = null)
    {
        $this->update([
            'patrimoniado'          => true,
            'destino_patrimonial_id' => $destinoId,
            'cargo_id'              => $cargoId,
            'fecha_patrimonio'      => $fecha ?? now()->toDateString(),
        ]);
    }

    /**
     * Limpiar patrimonio del equipo
     */
    public function despatrimoniar()
    {
        $this->update([
            'patrimoniado'          => false,
            'destino_patrimonial_id' => null,
            'cargo_id'              => null,
            'fecha_patrimonio'      => null,
        ]);
    }

    /**
     * Obtener el estado patrimonial formateado
     */
    public function getEstadoPatrimonialAttribute()
    {
        if (!$this->patrimoniado) {
            return 'sin_patrimoniar';
        }

        if ($this->cargo && $this->cargo->estado === 'pendiente') {
            return 'pendiente_firma';
        }

        return 'patrimoniado';
    }
}
