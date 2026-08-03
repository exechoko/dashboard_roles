<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ArmeriaAdjunto extends Model
{
    protected $table = 'armeria_adjuntos';

    protected $fillable = [
        'adjuntable_type',
        'adjuntable_id',
        'tipo',
        'ruta',
        'nombre_original',
        'mime',
        'tamano',
        'user_id',
    ];

    protected $casts = [
        'tamano' => 'integer',
    ];

    public function adjuntable(): MorphTo
    {
        return $this->morphTo();
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getUrlAttribute(): string
    {
        return \Illuminate\Support\Facades\Storage::disk('anexos')->url($this->ruta);
    }

    public function getIconoAttribute(): string
    {
        if ($this->tipo === 'IMAGEN') {
            return 'fa-file-image';
        }

        return match ($this->mime) {
            'application/pdf' => 'fa-file-pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'fa-file-word',
            default => 'fa-file',
        };
    }
}
