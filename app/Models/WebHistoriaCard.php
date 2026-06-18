<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebHistoriaCard extends Model
{
    use HasFactory;

    protected $table = 'web_historia_cards';

    protected $fillable = [
        'anio',
        'titulo',
        'texto',
        'tag',
        'imagen',
        'orden',
    ];
}
