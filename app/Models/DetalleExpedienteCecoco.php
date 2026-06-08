<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetalleExpedienteCecoco extends Model
{
    use HasFactory;

    protected $table = 'detalle_expediente_cecoco';

    protected $fillable = [
        'evento_cecoco_id',
        'nro_expediente',
        'detalle_json',
        'fecha_consulta',
        'resumen_ia',
        'resumen_ia_generado_en',
    ];

    protected $casts = [
        'detalle_json' => 'array',
        'fecha_consulta' => 'datetime',
        'resumen_ia' => 'array',
        'resumen_ia_generado_en' => 'datetime',
    ];

    public function eventoCecoco(): BelongsTo
    {
        return $this->belongsTo(EventoCecoco::class, 'evento_cecoco_id');
    }
}
