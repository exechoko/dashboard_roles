<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParteDiarioAsignacion extends Model
{
    protected $table = 'parte_diario_asignaciones';

    protected $fillable = [
        'parte_diario_id',
        'grupo',
        'nombre',
        'asignacion_texto',
        'orden',
    ];

    protected $casts = [
        'orden' => 'integer',
    ];

    public function parteDiario(): BelongsTo
    {
        return $this->belongsTo(ParteDiario::class);
    }
}
