<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TranscripcionJob extends Model
{
    protected $table = 'transcripcion_jobs';

    protected $fillable = [
        'audio_path',
        'status',
        'result_text',
        'error_message',
        'duration_seconds',
    ];
}
