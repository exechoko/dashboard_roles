<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatMensaje extends Model
{
    use HasFactory;

    protected $fillable = [
        'chat_conversacion_id',
        'user_id',
        'cuerpo',
    ];

    public function conversacion(): BelongsTo
    {
        return $this->belongsTo(ChatConversacion::class, 'chat_conversacion_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function adjuntos(): HasMany
    {
        return $this->hasMany(ChatAdjunto::class);
    }

    /**
     * @return array{id: int, conversacion_id: int, usuario_id: int, usuario: string, cuerpo: string|null, adjuntos: array<int, array{id: int, nombre: string, mime: string, tamano: int, url: string}>, creado_en: string|null}
     */
    public function paraChat(): array
    {
        return [
            'id' => $this->id,
            'conversacion_id' => $this->chat_conversacion_id,
            'usuario_id' => $this->user_id,
            'usuario' => trim($this->usuario->name . ' ' . $this->usuario->apellido),
            'cuerpo' => $this->cuerpo,
            'adjuntos' => $this->adjuntos->map(fn (ChatAdjunto $adjunto): array => [
                'id' => $adjunto->id,
                'nombre' => $adjunto->nombre_original,
                'mime' => $adjunto->mime,
                'tamano' => $adjunto->tamano,
                'url' => route('chat.adjuntos.show', $adjunto),
            ])->values()->all(),
            'creado_en' => $this->created_at?->toIso8601String(),
        ];
    }
}
