<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class HistorialConversionAudio extends Model
{
    use HasFactory;

    protected $table = 'historial_conversion_audios';

    protected $fillable = [
        'user_id',
        'nombre_archivo',
        'extension_original',
        'exito',
        'mensaje_error',
    ];

    protected $casts = [
        'exito' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
