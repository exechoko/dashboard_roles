<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoUso extends Model
{
    protected $table = 'tipo_uso';

    public function tipo_terminal(){
        return $this->hasMany(TipoTerminal::class);
    }
}
