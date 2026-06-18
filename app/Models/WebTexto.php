<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebTexto extends Model
{
    use HasFactory;

    protected $table = 'web_textos';

    protected $fillable = ['clave', 'valor'];

    /**
     * Devuelve los textos guardados como un mapa clave => valor.
     *
     * @return array<string, string>
     */
    public static function comoMapa(): array
    {
        return static::query()->pluck('valor', 'clave')->toArray();
    }
}
