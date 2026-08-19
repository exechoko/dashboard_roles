<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MailMensaje extends Model
{
    protected $table = 'mail_mensajes';

    public const CARPETAS = [
        'recibidos' => 'Recibidos',
        'enviados' => 'Enviados',
        'borradores' => 'Borradores',
        'spam' => 'Spam',
        'papelera' => 'Papelera',
        'archivados' => 'Archivados',
    ];

    protected $fillable = [
        'buzon_id',
        'archivo_id',
        'byte_offset',
        'byte_length',
        'message_id',
        'gm_thread_id',
        'de_nombre',
        'de_email',
        'para',
        'cc',
        'responder_a',
        'asunto',
        'fecha',
        'etiquetas',
        'carpeta',
        'tiene_adjuntos',
        'cantidad_adjuntos',
        'adjuntos_json',
        'adjuntos_nombres',
        'tamano_bytes',
        'tiene_html',
        'cuerpo_truncado',
        'snippet',
        'cuerpo_texto',
    ];

    protected $casts = [
        'fecha' => 'datetime',
        'tiene_adjuntos' => 'boolean',
        'tiene_html' => 'boolean',
        'cuerpo_truncado' => 'boolean',
        'adjuntos_json' => 'array',
    ];

    public function buzon(): BelongsTo
    {
        return $this->belongsTo(MailBuzon::class, 'buzon_id');
    }

    public function archivo(): BelongsTo
    {
        return $this->belongsTo(MailArchivo::class, 'archivo_id');
    }
}
