<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecursoVehiculoAsignacion extends Model
{
    protected $table = 'recurso_vehiculo_asignaciones';

    protected $fillable = ['recurso_id', 'vehiculo_id', 'fecha_desde', 'fecha_hasta', 'usuario_id', 'motivo'];

    protected $casts = [
        'fecha_desde' => 'date',
        'fecha_hasta' => 'date',
    ];

    public function recurso(): BelongsTo
    {
        return $this->belongsTo(Recurso::class);
    }

    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function estaActiva(): bool
    {
        return $this->fecha_hasta === null;
    }
}
