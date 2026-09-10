<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecursoBitacoraSeguimiento extends Model
{
    protected $table = 'recurso_bitacora_seguimientos';

    protected $fillable = [
        'bitacora_id',
        'user_id',
        'descripcion',
    ];

    public function bitacora(): BelongsTo
    {
        return $this->belongsTo(RecursoBitacora::class, 'bitacora_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function adjuntos(): HasMany
    {
        return $this->hasMany(RecursoBitacoraAdjunto::class, 'seguimiento_id');
    }
}
