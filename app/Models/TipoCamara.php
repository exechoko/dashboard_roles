<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoCamara extends Model
{
    protected $table = 'tipo_camara';

    public function camara(){
        return $this->hasMany(Camara::class);
    }
}
