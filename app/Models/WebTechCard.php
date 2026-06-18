<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebTechCard extends Model
{
    use HasFactory;

    protected $table = 'web_tech_cards';

    protected $fillable = [
        'titulo',
        'texto',
        'color',
        'imagen',
        'orden',
    ];
}
