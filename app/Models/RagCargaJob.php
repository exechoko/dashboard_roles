<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RagCargaJob extends Model
{
    protected $table = 'rag_carga_jobs';

    protected $fillable = [
        'archivo_path',
        'archivo_nombre',
        'coleccion',
        'resumir',
        'status',
        'resumen',
        'documentos_total',
        'error_message',
    ];

    protected $casts = [
        'resumir' => 'boolean',
    ];
}
