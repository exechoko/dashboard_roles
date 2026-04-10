<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CallAnalysisJob extends Model
{
    protected $table = 'call_analysis_jobs';

    protected $fillable = [
        'audio_path',
        'original_name',
        'mode',
        'status',
        'result_json',
        'error_message',
    ];

    public function getResultAttribute(): ?array
    {
        return $this->result_json ? json_decode($this->result_json, true) : null;
    }
}
