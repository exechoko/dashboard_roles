<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActuacionPolicial extends Model
{
    protected $table = 'actuaciones_policiales';

    public function destino(){
        return $this->belongsTo(Destino::class);
    }

    public function equipo(){
        return $this->belongsTo(Equipo::class);
    }

}
