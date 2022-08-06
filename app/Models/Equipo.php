<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Equipo extends Model
{
    protected $table = 'equipos';
    protected $fillable = ['issi', 'tei'];

    public function tipo_terminal(){
        return $this->belongsTo(TipoTerminal::class);
    }

    public function estado(){
        return $this->belongsTo(Estado::class);
    }
}
