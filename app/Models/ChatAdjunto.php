<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatAdjunto extends Model
{
    use HasFactory;

    protected $fillable = [
        'chat_mensaje_id',
        'nombre_original',
        'ruta',
        'mime',
        'tamano',
    ];

    public function mensaje(): BelongsTo
    {
        return $this->belongsTo(ChatMensaje::class, 'chat_mensaje_id');
    }
}
