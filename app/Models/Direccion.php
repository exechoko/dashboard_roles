<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Direccion extends Model
{
    protected $table = 'direcciones';
    protected $fillable = [
        'nombre'
    ];

    public function departamental(){
        return $this->hasMany(Departamental::class);
    }

    public function division(){
        return $this->hasMany(Division::class);
    }
}
