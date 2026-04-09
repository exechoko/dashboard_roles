<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RagConsultaJob extends Model
{
    protected $table = 'rag_consulta_jobs';

    protected $fillable = [
        'pregunta',
        'coleccion',
        'status',
        'respuesta',
        'error_message',
    ];
}
