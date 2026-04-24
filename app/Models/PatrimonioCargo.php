<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatrimonioCargo extends Model
{
    use HasFactory;

    protected $table = 'patrimonio_cargos';

    protected $fillable = [
        'equipo_id',
        'destino_id',
        'historico_id',
        'firmante_nombre',
        'firmante_cargo',
        'firmante_legajo',
        'estado',
        'fecha_firma',
        'observaciones',
        'ruta_documento',
        'usuario_creador',
    ];

    protected $casts = [
        'fecha_firma' => 'datetime',
    ];

    const ESTADOS = [
        'pendiente' => 'Pendiente de firma',
        'firmado'   => 'Firmado',
        'rechazado' => 'Rechazado',
    ];

    // ─── Relaciones ─────────────────────────────────────────

    public function equipo()
    {
        return $this->belongsTo(Equipo::class);
    }

    public function destino()
    {
        return $this->belongsTo(Destino::class);
    }

    public function historico()
    {
        return $this->belongsTo(Historico::class);
    }

    public function flotaGeneral()
    {
        return $this->hasOne(FlotaGeneral::class, 'cargo_id');
    }

    // ─── Scopes ─────────────────────────────────────────────

    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    public function scopeFirmados($query)
    {
        return $query->where('estado', 'firmado');
    }

    public function scopeRechazados($query)
    {
        return $query->where('estado', 'rechazado');
    }

    public function scopePorDestino($query, $destinoId)
    {
        return $query->where('destino_id', $destinoId);
    }

    // ─── Accessors ──────────────────────────────────────────

    public function getEstadoFormateadoAttribute()
    {
        return self::ESTADOS[$this->estado] ?? $this->estado;
    }

    public function getBadgeClassAttribute()
    {
        return [
            'pendiente' => 'warning',
            'firmado'   => 'success',
            'rechazado' => 'danger',
        ][$this->estado] ?? 'secondary';
    }

    public function getBadgeIconAttribute()
    {
        return [
            'pendiente' => 'fas fa-clock',
            'firmado'   => 'fas fa-check-circle',
            'rechazado' => 'fas fa-times-circle',
        ][$this->estado] ?? 'fas fa-question-circle';
    }

    // ─── Métodos ────────────────────────────────────────────

    /**
     * Registrar firma del cargo patrimonial
     */
    public function firmar($nombre, $cargo = null, $legajo = null, $observaciones = null)
    {
        $this->update([
            'firmante_nombre' => $nombre,
            'firmante_cargo'  => $cargo,
            'firmante_legajo' => $legajo,
            'estado'          => 'firmado',
            'fecha_firma'     => now(),
            'observaciones'   => $observaciones,
        ]);

        return $this;
    }

    /**
     * Rechazar el cargo patrimonial
     */
    public function rechazar($observaciones = null)
    {
        $this->update([
            'estado'        => 'rechazado',
            'observaciones' => $observaciones,
        ]);

        return $this;
    }

    /**
     * Verificar si el cargo está pendiente de firma
     */
    public function estaPendiente()
    {
        return $this->estado === 'pendiente';
    }

    /**
     * Verificar si el cargo está firmado
     */
    public function estaFirmado()
    {
        return $this->estado === 'firmado';
    }
}
