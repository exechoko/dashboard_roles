<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FlotaGeneral extends Model
{
    protected $table = 'flota_general';

    public function equipo(){
        return $this->belongsTo(Equipo::class);
    }

    public function recurso(){
        return $this->belongsTo(Recurso::class);
    }

    public function destino(){
        return $this->belongsTo(Destino::class);
    }
}
