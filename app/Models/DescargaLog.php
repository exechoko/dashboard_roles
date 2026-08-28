<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DescargaLog extends Model
{
    use HasFactory;

    protected $table = 'descarga_logs';

    public $timestamps = false;

    protected $fillable = [
        'archivo_id',
        'user_id',
        'ip_address',
        'user_agent',
        'link_publico_id',
        'downloaded_at',
    ];

    protected $casts = [
        'downloaded_at' => 'datetime',
    ];

    public function archivo(): BelongsTo
    {
        return $this->belongsTo(DescargaArchivo::class, 'archivo_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function linkPublico(): BelongsTo
    {
        return $this->belongsTo(DescargaLinkPublico::class, 'link_publico_id');
    }
}
