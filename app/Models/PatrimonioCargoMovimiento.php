<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatrimonioCargoMovimiento extends Model
{
    use HasFactory;

    protected $table = 'patrimonio_cargo_movimientos';

    protected $fillable = [
        'cargo_id',
        'equipo_id',
        'flota_id',
        'destino_origen_id',
        'destino_destino_id',
        'historico_id',
        'tipo_movimiento_id',
        'motivo',
        'usuario',
        'fecha',
    ];

    protected $casts = [
        'fecha' => 'datetime',
    ];

    // ─── Relaciones ─────────────────────────────────────────

    public function cargo()
    {
        return $this->belongsTo(PatrimonioCargo::class, 'cargo_id');
    }

    public function equipo()
    {
        return $this->belongsTo(Equipo::class, 'equipo_id');
    }

    public function flota()
    {
        return $this->belongsTo(FlotaGeneral::class, 'flota_id');
    }

    public function destinoOrigen()
    {
        return $this->belongsTo(Destino::class, 'destino_origen_id');
    }

    public function destinoDestino()
    {
        return $this->belongsTo(Destino::class, 'destino_destino_id');
    }

    public function historico()
    {
        return $this->belongsTo(Historico::class, 'historico_id');
    }

    public function tipoMovimiento()
    {
        return $this->belongsTo(TipoMovimiento::class, 'tipo_movimiento_id');
    }
}
