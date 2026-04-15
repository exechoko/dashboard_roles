<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TranscripcionJob extends Model
{
    protected $table = 'transcripcion_jobs';

    protected $fillable = [
        'audio_path',
        'original_filename',
        'telefono',
        'status',
        'result_text',
        'result_json',
        'error_message',
        'duration_seconds',
    ];
}
