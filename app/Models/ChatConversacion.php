<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ChatConversacion extends Model
{
    use HasFactory;

    protected $table = 'chat_conversaciones';

    protected $fillable = [
        'tipo',
        'nombre',
        'creado_por',
    ];

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function participantes(): HasMany
    {
        return $this->hasMany(ChatParticipante::class);
    }

    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'chat_participantes')
            ->withPivot(['es_admin', 'ultimo_leido_id', 'ultimo_leido_at'])
            ->withTimestamps();
    }

    public function mensajes(): HasMany
    {
        return $this->hasMany(ChatMensaje::class);
    }

    public function ultimoMensaje(): HasOne
    {
        return $this->hasOne(ChatMensaje::class)->latestOfMany();
    }
}
