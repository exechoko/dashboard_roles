<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Division extends Model
{
    protected $table = 'divisiones';
    protected $fillable = [
        'nombre'
    ];

    public function direccion(){
        return $this->belongsTo(Direccion::class);
    }

    public function departamental(){
        return $this->belongsTo(Departamental::class);
    }

    public function seccion(){
        return $this->hasMany(Seccion::class);
    }

    public function destacamento(){
        return $this->hasMany(Destacamento::class);
    }

}
