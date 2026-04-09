<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RagChatMensaje extends Model
{
    protected $table = 'rag_chat_mensajes';

    protected $fillable = [
        'user_id',
        'coleccion',
        'role',
        'contenido',
    ];
}
