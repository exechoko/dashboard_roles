<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArmasAnterior extends Model
{
    protected $table = 'armas_anteriores';

    protected $fillable = [
        'personal_id',
        'numeracion_arma',
        'arma_tipo_id',
        'nro_chaleco',
        'fecha_cambio',
        'motivo_cambio',
        'created_by',
    ];

    public function personal(): BelongsTo
    {
        return $this->belongsTo(Personal::class);
    }

    public function armaTipo(): BelongsTo
    {
        return $this->belongsTo(ArmaTipo::class);
    }

    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
