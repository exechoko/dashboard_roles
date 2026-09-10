<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecursoBitacoraVista extends Model
{
    protected $table = 'recurso_bitacora_vistas';

    protected $fillable = [
        'recurso_id',
        'user_id',
        'visto_en',
    ];

    protected $casts = [
        'visto_en' => 'datetime',
    ];

    public function recurso(): BelongsTo
    {
        return $this->belongsTo(Recurso::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
