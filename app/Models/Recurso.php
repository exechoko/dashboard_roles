<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Recurso extends Model
{
    protected $table = 'recursos';

    public function vehiculo(){
        return $this->hasOne(Vehiculo::class);
    }
}
