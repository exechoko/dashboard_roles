<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoTerminal extends Model
{
    protected $table = 'tipo_terminales';

    public function tipo_uso(){
        return $this->belongsTo(TipoUso::class);
    }

    public function equipo(){
        return $this->hasMany(Equipo::class);
    }
}
