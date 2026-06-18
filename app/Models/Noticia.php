<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Noticia extends Model
{
    use HasFactory;

    protected $table = 'noticias';

    protected $fillable = [
        'titulo',
        'bajada',
        'cuerpo',
        'fecha_publicacion',
        'publicada',
    ];

    protected $casts = [
        'fecha_publicacion' => 'date',
        'publicada'         => 'boolean',
    ];

    /**
     * @return HasMany<NoticiaImagen>
     */
    public function imagenes(): HasMany
    {
        return $this->hasMany(NoticiaImagen::class)->orderBy('orden');
    }

    /**
     * Imagen marcada como miniatura (o la primera por orden si ninguna lo está).
     *
     * @return HasOne<NoticiaImagen>
     */
    public function miniatura(): HasOne
    {
        return $this->hasOne(NoticiaImagen::class)
            ->orderByDesc('es_miniatura')
            ->orderBy('orden');
    }
}
