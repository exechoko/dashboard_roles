<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecursoNovedadAdjunto extends Model
{
    protected $table = 'recurso_novedad_adjuntos';

    protected $fillable = ['novedad_id', 'user_id', 'ruta', 'nombre_original', 'mime_type'];

    public function novedad(): BelongsTo
    {
        return $this->belongsTo(RecursoNovedad::class, 'novedad_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function esImagen(): bool
    {
        return str_starts_with($this->mime_type ?? '', 'image/');
    }
}
