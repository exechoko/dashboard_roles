<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Accesorio extends Model
{
    protected $table = 'accesorio';

    public function tipo_accesorio(){
        return $this->belongsTo(TipoAccesorios::class);
    }
}
