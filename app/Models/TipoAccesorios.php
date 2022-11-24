<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoAccesorios extends Model
{
    protected $table = 'tipo_accesorio';

    public function accesorio(){
        return $this->hasMany(Equipo::class);
    }
}
