<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonalChalecoAsignacion extends Model
{
    use HasFactory;

    protected $table = 'personal_chaleco_asignaciones';

    protected $fillable = ['personal_id', 'chaleco_id', 'fecha_desde', 'fecha_hasta', 'activa', 'origen'];

    protected $casts = [
        'fecha_desde' => 'date',
        'fecha_hasta' => 'date',
        'activa' => 'boolean',
    ];

    public function personal(): BelongsTo
    {
        return $this->belongsTo(Personal::class);
    }

    public function chaleco(): BelongsTo
    {
        return $this->belongsTo(Chaleco::class);
    }
}
