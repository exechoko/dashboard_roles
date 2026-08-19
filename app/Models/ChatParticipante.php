<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatParticipante extends Model
{
    use HasFactory;

    protected $fillable = [
        'chat_conversacion_id',
        'user_id',
        'es_admin',
        'ultimo_leido_id',
        'ultimo_leido_at',
        'aviso_no_leido_enviado_at',
    ];

    protected $casts = [
        'es_admin' => 'boolean',
        'ultimo_leido_at' => 'datetime',
        'aviso_no_leido_enviado_at' => 'datetime',
    ];

    public function conversacion(): BelongsTo
    {
        return $this->belongsTo(ChatConversacion::class, 'chat_conversacion_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
