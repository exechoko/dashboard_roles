<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DescargaZipTemporal extends Model
{
    protected $table = 'descarga_zips_temporales';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'token',
        'ruta_zip',
        'tamano_bytes',
        'expira_at',
        'descargado',
        'created_at',
    ];

    protected $casts = [
        'tamano_bytes' => 'integer',
        'descargado' => 'boolean',
        'expira_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeExpirados($query)
    {
        return $query->where('expira_at', '<', now());
    }

    public function scopeValidos($query)
    {
        return $query->where('expira_at', '>', now());
    }

    public function getTamanoHumanoAttribute(): string
    {
        $bytes = $this->tamano_bytes;

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
