<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoTerminal extends Model
{
    protected $table = 'tipo_terminales';

    public function equipo(){
        return $this->hasMany(Equipo::class);
    }
}
