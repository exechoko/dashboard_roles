<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DescargaVersion extends Model
{
    use HasFactory;

    protected $table = 'descarga_versiones';

    public $timestamps = false;

    protected $fillable = [
        'archivo_id',
        'version_numero',
        'nombre_archivo_anterior',
        'ruta_anterior',
        'tamano_anterior',
        'user_id',
        'motivo',
        'created_at',
    ];

    protected $casts = [
        'version_numero' => 'integer',
        'tamano_anterior' => 'integer',
        'created_at' => 'datetime',
    ];

    public function archivo(): BelongsTo
    {
        return $this->belongsTo(DescargaArchivo::class, 'archivo_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getTamanoHumanoAttribute(): string
    {
        $bytes = $this->tamano_anterior;

        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' bytes';
    }
}
