<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonalArmaAsignacion extends Model
{
    use HasFactory;

    protected $table = 'personal_arma_asignaciones';

    protected $fillable = ['personal_id', 'arma_id', 'fecha_desde', 'fecha_hasta', 'activa', 'origen'];

    protected $casts = [
        'fecha_desde' => 'date',
        'fecha_hasta' => 'date',
        'activa' => 'boolean',
    ];

    public function personal(): BelongsTo
    {
        return $this->belongsTo(Personal::class);
    }

    public function arma(): BelongsTo
    {
        return $this->belongsTo(Arma::class);
    }
}
