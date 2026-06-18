<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebConfigDato extends Model
{
    use HasFactory;

    protected $table = 'web_config_datos';

    protected $fillable = ['clave', 'valor'];

    protected $casts = [
        'valor' => 'array',
    ];

    /**
     * Devuelve todos los datos como un mapa clave => valor.
     *
     * @return array<string, mixed>
     */
    public static function comoMapa(): array
    {
        return static::query()->pluck('valor', 'clave')->toArray();
    }
}
