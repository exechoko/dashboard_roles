<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RagTematica extends Model
{
    protected $table = 'rag_tematicas';

    protected $fillable = ['nombre', 'coleccion', 'descripcion'];

    /**
     * Genera el slug de colección a partir del nombre (igual que el servidor Python).
     */
    public static function slugDesdeNombre(string $nombre): string
    {
        $slug = mb_strtolower(trim($nombre));
        $slug = preg_replace('/[^a-z0-9_\-]/', '_', $slug);
        $slug = preg_replace('/_+/', '_', $slug);
        return trim($slug, '_') ?: 'general';
    }
}
