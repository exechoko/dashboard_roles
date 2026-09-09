<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DescargaComentario extends Model
{
    use HasFactory;

    protected $table = 'descarga_comentarios';

    protected $fillable = [
        'archivo_id',
        'user_id',
        'comentario',
        'es_admin',
    ];

    protected $casts = [
        'es_admin' => 'boolean',
    ];

    public function archivo(): BelongsTo
    {
        return $this->belongsTo(DescargaArchivo::class, 'archivo_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
