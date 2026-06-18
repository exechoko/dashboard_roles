<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NoticiaImagen extends Model
{
    use HasFactory;

    protected $table = 'noticia_imagenes';

    protected $fillable = [
        'noticia_id',
        'archivo',
        'es_miniatura',
        'orden',
    ];

    protected $casts = [
        'es_miniatura' => 'boolean',
    ];

    /**
     * @return BelongsTo<Noticia, NoticiaImagen>
     */
    public function noticia(): BelongsTo
    {
        return $this->belongsTo(Noticia::class);
    }
}
